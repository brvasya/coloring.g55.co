import tkinter as tk
from tkinter import ttk
from tkinter import messagebox
import random
import os
import re
import json

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CATEGORIES_DIR = os.path.join(BASE_DIR, "categories")
STYLE_FILE = os.path.join(CATEGORIES_DIR, "style.txt")

LIST_NAMES = ["characters", "actions", "environments", "extras"]
COPIED_BG = "systemHighlight"


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


def title_case_simple(s):
    return " ".join(w.capitalize() for w in s.split())


def slugify(text):
    text = text.lower()
    text = re.sub(r"[^a-z0-9]+", "-", text)
    text = re.sub(r"-{2,}", "-", text).strip("-")
    return text or "coloring-page"


def normalize_action_for_title(action):
    s = action.strip()
    s = re.sub(r"\s+", " ", s)
    s = re.sub(
        r"\b(in|through|across|over|into|inside|around)\b\s*$",
        "",
        s,
        flags=re.IGNORECASE,
    ).strip()
    s = re.sub(
        r"\b(in|through|across|over|into|inside|around)\b\s+",
        " ",
        s,
        flags=re.IGNORECASE,
    ).strip()
    return s


def build_h1(parts):
    character = parts["character"].strip()
    action = normalize_action_for_title(parts["action"])
    env = parts["environment"].strip()

    base = f"{character} {action} {env}"
    base = re.sub(r"\s{2,}", " ", base).strip()
    return f"Free Printable {title_case_simple(base)} Coloring Page for Kids"


def build_seo_base_for_slug(parts):
    character = parts["character"].strip()
    action = normalize_action_for_title(parts["action"])
    env = parts["environment"].strip()

    base = f"{character} {action} {env} coloring page"
    base = re.sub(r"\s{2,}", " ", base).strip()
    return base


def build_id(parts):
    return slugify(build_seo_base_for_slug(parts))


def format_extra_clause(extra):
    return f"with {extra.strip()}"


def build_prompt(parts, style):
    extra_clause = format_extra_clause(parts["extra"])
    core = f"{parts['character']} {parts['action']} {parts['environment']}"
    core = re.sub(r"\s{2,}", " ", core).strip()
    core = f"{core} {extra_clause}"
    return "Coloring page on white background, " f"{core}, " f"{style}."


def _clean_sentence(s):
    s = re.sub(r"\s{2,}", " ", (s or "").strip())
    s = s.strip(" ,")
    if not s.endswith("."):
        s += "."
    return s


def build_page_description(parts):
    character = parts["character"].strip()
    action = parts["action"].strip()
    env = parts["environment"].strip()
    extra_clause = format_extra_clause(parts["extra"])

    scene = f"{character} {action} {env} {extra_clause}"
    scene = re.sub(r"\s{2,}", " ", scene).strip()

    intro_pool = [
        f"This printable coloring page features {scene} in a fun scene to color.",
        f"Enjoy coloring {scene} on this free printable page for kids.",
        f"This coloring sheet shows {scene} using clean lines and simple shapes.",
        f"Download and print a coloring page of {scene} for creative play.",
        f"Kids will have fun coloring {scene} in this easy printable design.",
        f"This free coloring page includes {scene} as the main scene to color.",
        f"Print this coloring page and color {scene} for a relaxing activity.",
        f"A cute coloring page shows {scene} in a clear black and white style.",
        f"Coloring time is easy with this printable page featuring {scene}.",
        f"This kid friendly coloring page shows {scene} using bold outlines.",
        f"Get a free printable coloring page showing {scene} for home or school.",
        f"This simple coloring page lets kids color {scene}.",
    ]

    usage_pool = [
        "The black and white line art is designed for easy printing.",
        "Perfect for home activities, classrooms, and weekend fun.",
        "Great for quiet time, rainy days, and creative breaks.",
        "A simple printable page for quick coloring sessions.",
        "Ideal for teachers, parents, and kids who want an easy activity.",
        "Print it anytime for a screen free craft and coloring moment.",
        "Works well for party activities, family time, or after school fun.",
        "A fun printable for travel, waiting rooms, or cozy evenings.",
        "Easy to print and share for group coloring or solo coloring.",
        "Made for simple printing and fast setup at home.",
        "A handy printable activity for school projects and art centers.",
        "A simple print and color page that fits many kids activities.",
    ]

    ease_pool = [
        "Thick outlines make the drawing easy to color and easy to follow.",
        "Large open areas are great for crayons, markers, or colored pencils.",
        "Simple shapes help younger kids color without frustration.",
        "Clean outlines keep the page clear and enjoyable to finish.",
        "Big coloring spaces are friendly for small hands and early learners.",
        "The minimal background detail keeps the focus on the main scene.",
        "The design is easy to color, even for beginners.",
        "Bold lines help colors stay inside the shapes more easily.",
        "A clear layout makes coloring relaxing and straightforward.",
        "The page is designed for quick printing and simple coloring.",
        "Easy outlines make it a good fit for preschool and elementary kids.",
        "Simple line art helps kids complete the page with confidence.",
    ]

    benefit_pool = [
        "Coloring supports creativity and fine motor skills.",
        "This activity helps kids practice focus and patience.",
        "A fun way to build imagination through coloring.",
        "Coloring can support hand control and coordination.",
        "A relaxing activity that encourages calm creative time.",
        "Helps kids explore colors while enjoying a simple scene.",
        "Great for developing art confidence with an easy design.",
        "Encourages creative choices and storytelling through color.",
        "Supports early learning skills like color recognition.",
        "A simple activity that can keep kids engaged and happy.",
        "A nice way to enjoy screen free time with a printable page.",
        "Coloring helps kids slow down and enjoy a creative moment.",
    ]

    tail_pool = [
        "Free printable coloring page for kids.",
        "Printable black and white line art for easy coloring.",
        "A simple printable coloring activity for kids.",
        "A fun coloring sheet to print and color anytime.",
        "A kid friendly printable coloring page for home or school.",
        "A clean line art printable that is easy to color.",
        "A quick print coloring page for creative time.",
        "An easy printable for crayons, markers, or pencils.",
        "A simple coloring page with bold outlines.",
        "A printable coloring page designed for easy coloring.",
        "A free printable page for relaxing coloring time.",
        "A fun printable coloring sheet for young artists.",
    ]

    # Multiple layout patterns to reduce repeated ordering
    # Pattern A: intro + usage + ease + benefit
    # Pattern B: intro + ease + usage + benefit
    # Pattern C: intro + usage + benefit
    # Pattern D: intro + ease + benefit
    patterns = [
        ("intro", "usage", "ease", "benefit"),
        ("intro", "ease", "usage", "benefit"),
        ("intro", "usage", "benefit"),
        ("intro", "ease", "benefit"),
    ]

    pools = {
        "intro": intro_pool,
        "usage": usage_pool,
        "ease": ease_pool,
        "benefit": benefit_pool,
    }

    pattern = random.choice(patterns)

    sentences = [random.choice(pools[key]) for key in pattern]

    # Optional tail, added sometimes to diversify output
    if random.random() < 0.35:
        sentences.append(random.choice(tail_pool))

    return " ".join(_clean_sentence(s) for s in sentences)


def generate_item(data):
    if not all(data.get(k) for k in LIST_NAMES) or not data.get("style"):
        parts = {"character": "", "action": "", "environment": "", "extra": ""}
        return {
            "parts": parts,
            "h1": "Missing files",
            "id": "missing-files",
            "prompt": "Missing files",
            "page_description": "Missing files",
        }

    parts = {
        "character": random.choice(data["characters"]),
        "action": random.choice(data["actions"]),
        "environment": random.choice(data["environments"]),
        "extra": random.choice(data["extras"]),
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
        self.count_var = tk.IntVar(value=10)

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

        ttk.Button(top, text="Save All", command=self.save_all_to_json).pack(side="left")

        ttk.Separator(self).pack(fill="x", padx=10, pady=(0, 10))

        container = ttk.Frame(self)
        container.pack(fill="both", expand=True, padx=10, pady=(0, 10))

        self.canvas = tk.Canvas(container, highlightthickness=0)
        scrollbar = ttk.Scrollbar(
            container, orient="vertical", command=self.canvas.yview
        )

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

    def on_category_change(self, _event=None):
        self.data = load_category_data(self.category_var.get())
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
                text="Save",
                command=lambda idx=i: self.save_one_to_json(idx),
            ).pack(side="top", fill="x")

            self.rows.append((card, indicator))


if __name__ == "__main__":
    try:
        from ctypes import windll

        windll.shcore.SetProcessDpiAwareness(1)
    except Exception:
        pass

    PromptGUI().mainloop()