import tkinter as tk
from tkinter import ttk
from tkinter import messagebox
import random
import os
import re
import json

import threading
from datetime import datetime
from io import BytesIO
import base64

# Optional deps for image generation
try:
    from google import genai
    from google.genai import types
except Exception:
    genai = None
    types = None

try:
    from PIL import Image
except Exception:
    Image = None


BASE_DIR = os.path.dirname(os.path.abspath(__file__))
APP_DIR = os.path.join(BASE_DIR, "app")

CATEGORIES_DIR = os.path.join(BASE_DIR, "categories")
STYLE_FILE = os.path.join(CATEGORIES_DIR, "style.txt")

GENERATED_IMAGES_DIR = os.path.join(BASE_DIR, "categories")

# Option A: remove extras completely
LIST_NAMES = ["characters", "actions", "environments"]

COPIED_BG = "systemHighlight"

# Pool txt files stored in BASE_DIR/app
POOL_FILES = {
    "intro": os.path.join(APP_DIR, "intro_pool.txt"),
    "usage": os.path.join(APP_DIR, "usage_pool.txt"),
    "ease": os.path.join(APP_DIR, "ease_pool.txt"),
    "benefit": os.path.join(APP_DIR, "benefit_pool.txt"),
}

# Loaded pools (populated at startup)
POOLS = {k: [] for k in POOL_FILES.keys()}


def list_category_folders():
    if not os.path.isdir(CATEGORIES_DIR):
        return []
    return sorted(
        d
        for d in os.listdir(CATEGORIES_DIR)
        if os.path.isdir(os.path.join(CATEGORIES_DIR, d))
    )


def load_lines(path):
    try:
        with open(path, "r", encoding="utf-8") as f:
            out = []
            for line in f:
                line = line.strip()
                if line and not line.startswith("#"):
                    out.append(line)
            return out
    except Exception:
        return []


def load_style():
    lines = load_lines(STYLE_FILE)
    return lines[0] if lines else ""


def load_category_data(category_name):
    cat_dir = os.path.join(CATEGORIES_DIR, category_name)
    data = {k: load_lines(os.path.join(cat_dir, f"{k}.txt")) for k in LIST_NAMES}
    data["style"] = load_style()
    return data


SMALL_WORDS = {
    "a",
    "an",
    "the",
    "at",
    "in",
    "on",
    "to",
    "for",
    "with",
    "and",
    "or",
    "of",
    "as",
}


def format_title(title: str) -> str:
    words = title.strip().split()
    out = []

    for i, word in enumerate(words):
        lw = word.lower()
        if i != 0 and lw in SMALL_WORDS:
            out.append(lw)
        else:
            out.append(lw.capitalize())

    return " ".join(out)


def slugify(text):
    text = text.lower()
    text = re.sub(r"[^a-z0-9]+", "-", text)
    text = re.sub(r"-{2,}", "-", text).strip("-")
    return text or "coloring-page"


def strip_leading_article(text):
    s = (text or "").strip()
    lower = s.lower()
    if lower.startswith("a "):
        return s[2:].lstrip()
    if lower.startswith("an "):
        return s[3:].lstrip()
    return s


def build_h1(parts):
    # Filter leading articles from character only for H1
    character = strip_leading_article(parts["character"])
    action = parts["action"].strip()
    env = parts["environment"].strip()

    base = f"{character} {action} {env}"
    base = re.sub(r"\s{2,}", " ", base).strip()
    return f"Free Printable {format_title(base)} Coloring Page for Kids"


def build_seo_base_for_slug(parts):
    # Filter leading articles from character only for id/slug
    character = strip_leading_article(parts["character"])
    action = parts["action"].strip()
    env = parts["environment"].strip()

    base = f"{character} {action} {env} coloring page"
    base = re.sub(r"\s{2,}", " ", base).strip()
    return base


def build_id(parts):
    return slugify(build_seo_base_for_slug(parts))


def build_prompt(parts, style):
    # Keep articles in prompt for correct grammar
    core = f"{parts['character']} {parts['action']} {parts['environment']}"
    core = re.sub(r"\s{2,}", " ", core).strip()
    return "Coloring page on white background, " f"{core}, " f"{style}."


def _clean_sentence(s):
    s = re.sub(r"\s{2,}", " ", (s or "").strip())
    s = s.strip(" ,")
    if not s.endswith("."):
        s += "."
    return s


def load_pools():
    for key, path in POOL_FILES.items():
        POOLS[key] = load_lines(path)


def render_template(line, scene):
    return (line or "").replace("{scene}", scene)


def build_page_description(parts):
    # Keep articles in description for correct grammar
    character = parts["character"].strip()
    action = parts["action"].strip()
    env = parts["environment"].strip()

    scene = f"{character} {action} {env}"
    scene = re.sub(r"\s{2,}", " ", scene).strip()

    intro_pool = POOLS.get("intro") or []
    usage_pool = POOLS.get("usage") or []
    ease_pool = POOLS.get("ease") or []
    benefit_pool = POOLS.get("benefit") or []

    patterns = [
        ("intro", "usage", "ease", "benefit"),
        ("intro", "usage", "benefit", "ease"),
        ("intro", "ease", "usage", "benefit"),
        ("intro", "ease", "benefit", "usage"),
        ("intro", "benefit", "usage", "ease"),
        ("intro", "benefit", "ease", "usage"),
    ]

    pools = {
        "intro": intro_pool,
        "usage": usage_pool,
        "ease": ease_pool,
        "benefit": benefit_pool,
    }

    if any(not pools[k] for k in ("intro", "usage", "ease", "benefit")):
        return "Missing pool files."

    pattern = random.choice(patterns)
    sentences = []

    for key in pattern:
        line = random.choice(pools[key])
        sentences.append(_clean_sentence(render_template(line, scene)))

    return " ".join(sentences)


def _safe_mkdir(p):
    try:
        os.makedirs(p, exist_ok=True)
    except Exception:
        pass


def _sanitize_filename(name: str) -> str:
    name = (name or "").strip()
    name = re.sub(r"[^a-zA-Z0-9._-]+", "_", name)
    name = re.sub(r"_+", "_", name).strip("_")
    return name or "image"


def _save_bw_png(img, out_path: str):
    gray = img.convert("L")
    bw = gray.point(lambda p: 255 if p >= 128 else 0, mode="1")
    bw.save(out_path, format="PNG")


def save_image_with_gemini(prompt: str, api_key: str, out_path: str):
    if genai is None or Image is None:
        raise RuntimeError("Missing dependency. Install: pip install google-genai pillow")

    api_key = (api_key or "").strip()
    client = genai.Client(api_key=api_key) if api_key else genai.Client()

    model = "imagen-4.0-fast-generate-001"
    out_path = os.path.abspath(out_path)

    _safe_mkdir(os.path.dirname(out_path))

    if types is None:
        raise RuntimeError("google-genai types not available")

    response = client.models.generate_images(
        model=model,
        prompt=prompt,
        config=types.GenerateImagesConfig(
            number_of_images=1,
            aspect_ratio="3:4",
        ),
    )

    generated_images = getattr(response, "generated_images", None) or []
    if not generated_images:
        raise RuntimeError("No images returned")

    gi = generated_images[0]
    img_obj = getattr(gi, "image", None)

    img_bytes = None
    if img_obj is not None:
        img_bytes = getattr(img_obj, "image_bytes", None) or getattr(img_obj, "bytes", None)

    if img_bytes is None:
        img_bytes = getattr(gi, "image_bytes", None) or getattr(gi, "bytes", None)

    if img_bytes is None:
        raise RuntimeError("Image bytes not found in response")

    if isinstance(img_bytes, str):
        img_bytes = base64.b64decode(img_bytes)

    img = Image.open(BytesIO(img_bytes))
    _save_bw_png(img, out_path)
    return out_path


def default_image_path(category_name: str, page_id: str) -> str:
    cat = _sanitize_filename(category_name)
    pid = _sanitize_filename(page_id)
    return os.path.join(GENERATED_IMAGES_DIR, cat, f"{pid}.png")


def generate_item(data):
    if not all(data.get(k) for k in LIST_NAMES) or not data.get("style"):
        parts = {"character": "", "action": "", "environment": ""}
        return {
            "parts": parts,
            "h1": "Missing files",
            "id": "missing-files",
            "prompt": "Missing files",
            "page_description": "Missing files",
        }

    if not all(POOLS.get(k) for k in ("intro", "usage", "ease", "benefit")):
        parts = {"character": "", "action": "", "environment": ""}
        return {
            "parts": parts,
            "h1": "Missing pool files",
            "id": "missing-pool-files",
            "prompt": "Missing pool files",
            "page_description": "Missing pool files",
        }

    parts = {
        "character": random.choice(data["characters"]),
        "action": random.choice(data["actions"]),
        "environment": random.choice(data["environments"]),
    }

    return {
        "parts": parts,
        "h1": build_h1(parts),
        "id": build_id(parts),
        "prompt": build_prompt(parts, data["style"]),
        "page_description": build_page_description(parts),
    }


def category_json_path(category_name):
    return os.path.join(CATEGORIES_DIR, f"{category_name}.json")


def prepend_pages_to_category_json(category_name, pages_to_add):
    path = category_json_path(category_name)

    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    if "pages" not in data or not isinstance(data["pages"], list):
        data["pages"] = []

    data["pages"] = list(pages_to_add) + data["pages"]

    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)


class PromptGUI(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("Coloring Prompt Generator")
        self.geometry("980x620")

        self.style = ttk.Style(self)
        self.base_bg = None
        self._setup_styles()

        self.categories = list_category_folders()

        self.category_var = tk.StringVar(
            value=self.categories[0] if self.categories else ""
        )
        self.count_var = tk.IntVar(value=2)

        self.api_key_var = tk.StringVar(value=os.environ.get("GEMINI_API_KEY") or os.environ.get("GOOGLE_API_KEY") or "")
        self.data = (
            load_category_data(self.category_var.get())
            if self.category_var.get()
            else {}
        )

        top = ttk.Frame(self, padding=10)
        top.pack(fill="x")

        ttk.Label(top, text="Category:").pack(side="left")

        combo = ttk.Combobox(
            top,
            textvariable=self.category_var,
            values=self.categories,
            width=18,
            state="readonly" if self.categories else "disabled",
        )
        combo.pack(side="left", padx=(6, 12))
        combo.bind("<<ComboboxSelected>>", self.on_category_change)

        ttk.Label(top, text="Items:").pack(side="left")

        ttk.Spinbox(top, from_=1, to=200, textvariable=self.count_var, width=6).pack(
            side="left", padx=(6, 12)
        )

        ttk.Button(top, text="Refresh", command=self.refresh_items).pack(
            side="left", padx=(0, 8)
        )

        ttk.Button(top, text="Save Images", command=self.generate_all_images).pack(
            side="left", padx=(0, 8)
        )

        ttk.Button(top, text="Save All", command=self.save_all_to_json).pack(side="left")

        ttk.Separator(top, orient="vertical").pack(side="left", fill="y", padx=10)

        ttk.Label(top, text="Gemini key:").pack(side="left")
        ttk.Entry(top, textvariable=self.api_key_var, width=26, show="*").pack(
            side="left", padx=(6, 12)
        )

        # Counters row
        self.counters_var = tk.StringVar(value="")
        counters = ttk.Label(top, textvariable=self.counters_var)
        counters.pack(side="right")

        ttk.Separator(self).pack(fill="x", padx=10, pady=(0, 10))

        container = ttk.Frame(self)
        container.pack(fill="both", expand=True, padx=10, pady=(0, 10))

        self.canvas = tk.Canvas(container, highlightthickness=0)
        scrollbar = ttk.Scrollbar(container, orient="vertical", command=self.canvas.yview)

        self.scrollable_frame = ttk.Frame(self.canvas)
        self.scrollable_frame.bind(
            "<Configure>",
            lambda _e: self.canvas.configure(scrollregion=self.canvas.bbox("all")),
        )

        self.canvas_window = self.canvas.create_window(
            (0, 0), window=self.scrollable_frame, anchor="nw"
        )

        self.canvas.configure(yscrollcommand=scrollbar.set)

        self.canvas.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")

        self.canvas.bind(
            "<Configure>",
            lambda e: self.canvas.itemconfig(self.canvas_window, width=e.width),
        )

        self.rows = []
        self.h1_vars = []
        self.id_vars = []
        self.prompt_vars = []
        self.desc_vars = []

        self.update_counters()
        self.refresh_items()

    def _setup_styles(self):
        base_bg = self.style.lookup("TFrame", "background")
        if not base_bg:
            base_bg = self.cget("bg")
        self.base_bg = base_bg

        self.style.configure(
            "Card.TFrame",
            relief="solid",
            borderwidth=1,
            background=base_bg,
        )

    def update_counters(self):
        data = self.data or {}
        c = len(data.get("characters") or [])
        a = len(data.get("actions") or [])
        e = len(data.get("environments") or [])
        self.counters_var.set(f"Characters: {c}  Actions: {a}  Environments: {e}")

    def on_category_change(self, _event=None):
        self.data = load_category_data(self.category_var.get())
        self.update_counters()
        self.refresh_items()

    def mark_row(self, idx):
        if idx >= len(self.rows):
            return
        try:
            _card, indicator = self.rows[idx]
            indicator.configure(bg=COPIED_BG)
        except Exception:
            pass

    def copy_id(self, idx):
        self.clipboard_clear()
        self.clipboard_append(self.id_vars[idx].get())
        self.update()
        self.mark_row(idx)

    def copy_prompt(self, idx):
        self.clipboard_clear()
        self.clipboard_append(self.prompt_vars[idx].get())
        self.update()
        self.mark_row(idx)

    def _run_bg(self, fn, on_ok=None, on_err=None):
        def worker():
            try:
                result = fn()
                if on_ok:
                    self.after(0, lambda: on_ok(result))
            except Exception as e:
                if on_err:
                    self.after(0, lambda: on_err(e))

        t = threading.Thread(target=worker, daemon=True)
        t.start()

    def generate_image(self, idx):
        category_name = self.category_var.get().strip()
        page_id = self.id_vars[idx].get().strip()
        prompt = self.prompt_vars[idx].get().strip()
        api_key = self.api_key_var.get()

        out_path = default_image_path(category_name, page_id)

        def job():
            return save_image_with_gemini(prompt=prompt, api_key=api_key, out_path=out_path)

        def ok(saved_path):
            self.mark_row(idx)
            messagebox.showinfo("Image saved", f"Saved: {saved_path}")

        def err(e):
            messagebox.showerror("Image error", str(e))

        self._run_bg(job, on_ok=ok, on_err=err)

    def generate_all_images(self):
        category_name = self.category_var.get().strip()
        api_key = self.api_key_var.get()

        prompts = [
            (i, self.id_vars[i].get().strip(), self.prompt_vars[i].get().strip())
            for i in range(len(self.id_vars))
        ]

        def job():
            saved = 0
            for _i, page_id, prompt in prompts:
                out_path = default_image_path(category_name, page_id)
                save_image_with_gemini(prompt=prompt, api_key=api_key, out_path=out_path)
                saved += 1
            return saved

        def ok(n):
            messagebox.showinfo("Images saved", f"Saved {n} images.")

        def err(e):
            messagebox.showerror("Image error", str(e))

        self._run_bg(job, on_ok=ok, on_err=err)

    def save_one_to_json(self, idx):
        category_name = self.category_var.get().strip()

        page = {
            "id": self.id_vars[idx].get(),
            "title": self.h1_vars[idx].get(),
            "description": self.desc_vars[idx].get(),
        }

        prepend_pages_to_category_json(category_name, [page])
        self.mark_row(idx)

        messagebox.showinfo("Saved", f"Added 1 page to top of {category_name}.json")

    def save_all_to_json(self):
        category_name = self.category_var.get().strip()

        pages = [
            {
                "id": self.id_vars[i].get(),
                "title": self.h1_vars[i].get(),
                "description": self.desc_vars[i].get(),
            }
            for i in range(len(self.h1_vars))
        ]

        prepend_pages_to_category_json(category_name, pages)

        messagebox.showinfo(
            "Saved", f"Added {len(pages)} pages to top of {category_name}.json"
        )

    def refresh_items(self):
        for child in self.scrollable_frame.winfo_children():
            child.destroy()

        self.rows.clear()
        self.h1_vars.clear()
        self.id_vars.clear()
        self.prompt_vars.clear()
        self.desc_vars.clear()

        self.update_counters()

        try:
            count = int(self.count_var.get())
        except Exception:
            count = 10
            self.count_var.set(10)

        for i in range(count):
            item = generate_item(self.data)

            h1_var = tk.StringVar(value=item["h1"])
            id_var = tk.StringVar(value=item["id"])
            desc_var = tk.StringVar(value=item["page_description"])
            prompt_var = tk.StringVar(value=item["prompt"])

            self.h1_vars.append(h1_var)
            self.id_vars.append(id_var)
            self.desc_vars.append(desc_var)
            self.prompt_vars.append(prompt_var)

            card = ttk.Frame(self.scrollable_frame, style="Card.TFrame", padding=10)
            card.pack(fill="x", pady=6)

            card.grid_columnconfigure(2, weight=1)

            indicator = tk.Frame(card, width=6, bg=self.base_bg)
            indicator.grid(row=0, column=0, rowspan=2, sticky="ns", padx=(0, 6))
            indicator.grid_propagate(False)

            idx_label = ttk.Label(card, text=f"{i+1}.", width=4, anchor="n")
            idx_label.grid(row=0, column=1, rowspan=2, sticky="nw", padx=(0, 10))

            text_block = ttk.Frame(card)
            text_block.grid(row=0, column=2, sticky="nsew")

            ttk.Label(
                text_block,
                textvariable=h1_var,
                wraplength=760,
                justify="left",
                anchor="w",
            ).pack(side="top", anchor="w", fill="x")

            ttk.Label(
                text_block,
                textvariable=id_var,
                wraplength=760,
                justify="left",
                anchor="w",
            ).pack(side="top", anchor="w", fill="x", pady=(4, 0))

            ttk.Label(
                text_block,
                textvariable=desc_var,
                wraplength=760,
                justify="left",
                anchor="w",
            ).pack(side="top", anchor="w", fill="x", pady=(6, 0))

            ttk.Label(
                text_block,
                textvariable=prompt_var,
                wraplength=760,
                justify="left",
                anchor="w",
            ).pack(side="top", anchor="w", fill="x", pady=(6, 0))

            btns = ttk.Frame(card)
            btns.grid(row=0, column=3, rowspan=2, sticky="ne", padx=(10, 0))

            ttk.Button(
                btns,
                text="Copy Id",
                command=lambda idx=i: self.copy_id(idx),
            ).pack(side="top", fill="x", pady=(0, 6))

            ttk.Button(
                btns,
                text="Copy Prompt",
                command=lambda idx=i: self.copy_prompt(idx),
            ).pack(side="top", fill="x", pady=(0, 6))

            ttk.Button(
                btns,
                text="Generate Image",
                command=lambda idx=i: self.generate_image(idx),
            ).pack(side="top", fill="x", pady=(0, 6))

            ttk.Button(
                btns,
                text="Save",
                command=lambda idx=i: self.save_one_to_json(idx),
            ).pack(side="top", fill="x")

            self.rows.append((card, indicator))


if __name__ == "__main__":
    load_pools()

    try:
        from ctypes import windll

        windll.shcore.SetProcessDpiAwareness(1)
    except Exception:
        pass

    PromptGUI().mainloop()