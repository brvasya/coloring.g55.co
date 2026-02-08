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

    return (
        "Coloring page on white background, "
        f"{core}, "
        f"{style}."
    )


def generate_item(data):
    if not all(data.get(k) for k in LIST_NAMES) or not data.get("style"):
        parts = {"character": "", "action": "", "environment": "", "extra": ""}
        return {
            "parts": parts,
            "h1": "Missing files",
            "id": "missing-files",
            "prompt": "Missing files",
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
        self.geometry("980x560")

        self.style = ttk.Style(self)
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

        ttk.Spinbox(
            top, from_=1, to=200, textvariable=self.count_var, width=6
        ).pack(side="left", padx=(6, 12))

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

        self.refresh_items()

    def _setup_styles(self):
        base_bg = self.style.lookup("TFrame", "background")
        if not base_bg:
            base_bg = self.cget("bg")

        self.style.configure(
            "Card.TFrame",
            relief="solid",
            borderwidth=1,
            background=base_bg,
        )
        self.style.configure(
            "Copied.Card.TFrame",
            relief="solid",
            borderwidth=1,
            background=COPIED_BG,
        )

    def on_category_change(self, _event=None):
        self.data = load_category_data(self.category_var.get())
        self.refresh_items()

    def mark_row(self, idx):
        if idx >= len(self.rows):
            return
        try:
            self.rows[idx].configure(style="Copied.Card.TFrame")
        except Exception:
            pass

    def copy_h1(self, idx):
        self.clipboard_clear()
        self.clipboard_append(self.h1_vars[idx].get())
        self.update()
        self.mark_row(idx)

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
            "description": self.prompt_vars[idx].get(),
        }

        prepend_pages_to_category_json(category_name, [page])
        self.mark_row(idx)

        messagebox.showinfo(
            "Saved",
            f"Added 1 page to top of {category_name}.json",
        )

    def save_all_to_json(self):
        category_name = self.category_var.get().strip()

        pages = [
            {
                "id": self.id_vars[i].get(),
                "title": self.h1_vars[i].get(),
                "description": self.prompt_vars[i].get(),
            }
            for i in range(len(self.h1_vars))
        ]

        prepend_pages_to_category_json(category_name, pages)

        messagebox.showinfo(
            "Saved",
            f"Added {len(pages)} pages to top of {category_name}.json",
        )

    def refresh_items(self):
        for child in self.scrollable_frame.winfo_children():
            child.destroy()

        self.rows.clear()
        self.h1_vars.clear()
        self.id_vars.clear()
        self.prompt_vars.clear()

        try:
            count = int(self.count_var.get())
        except Exception:
            count = 10
            self.count_var.set(10)

        for i in range(count):
            item = generate_item(self.data)

            h1_var = tk.StringVar(value=item["h1"])
            id_var = tk.StringVar(value=item["id"])
            prompt_var = tk.StringVar(value=item["prompt"])

            self.h1_vars.append(h1_var)
            self.id_vars.append(id_var)
            self.prompt_vars.append(prompt_var)

            card = ttk.Frame(
                self.scrollable_frame,
                style="Card.TFrame",
                padding=10,
            )
            card.pack(fill="x", pady=6)

            card.grid_columnconfigure(1, weight=1)

            idx_label = ttk.Label(card, text=f"{i+1}.", width=4, anchor="n")
            idx_label.grid(row=0, column=0, rowspan=2, sticky="nw", padx=(0, 10))

            text_block = ttk.Frame(card)
            text_block.grid(row=0, column=1, sticky="nsew")

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
                textvariable=prompt_var,
                wraplength=760,
                justify="left",
                anchor="w",
            ).pack(side="top", anchor="w", fill="x", pady=(6, 0))

            btns = ttk.Frame(card)
            btns.grid(row=0, column=2, rowspan=2, sticky="ne", padx=(10, 0))

            ttk.Button(
                btns, text="Copy Title", command=lambda idx=i: self.copy_h1(idx)
            ).pack(side="top", fill="x", pady=(0, 6))

            ttk.Button(
                btns, text="Copy Id", command=lambda idx=i: self.copy_id(idx)
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

            self.rows.append(card)


if __name__ == "__main__":
    try:
        from ctypes import windll
        windll.shcore.SetProcessDpiAwareness(1)
    except Exception:
        pass

    PromptGUI().mainloop()