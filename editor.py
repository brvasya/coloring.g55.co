import json
import os
import re
import tkinter as tk
from tkinter import ttk, messagebox

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CATEGORIES_DIR = os.path.join(SCRIPT_DIR, "categories")

TEMPLATE = {"id": "", "title": "", "description": ""}

def list_json_files(folder: str) -> list[str]:
    files = []
    try:
        for name in os.listdir(folder):
            if name.lower().endswith(".json") and os.path.isfile(os.path.join(folder, name)):
                files.append(name)
    except Exception:
        pass
    files.sort(key=lambda s: s.lower())
    return files

def slugify(text: str) -> str:
    text = text.strip().lower()
    text = re.sub(r"[^\w\s-]", "", text, flags=re.UNICODE)
    text = re.sub(r"[\s_]+", "-", text)
    text = re.sub(r"-{2,}", "-", text)
    return text.strip("-")

def normalize_loaded_json(loaded):
    if isinstance(loaded, dict) and "pages" in loaded and isinstance(loaded["pages"], list):
        return loaded["pages"], loaded
    if isinstance(loaded, list):
        return loaded, None
    raise ValueError("Unsupported JSON format. Expected {\"pages\": [...]} or a list.")

class JsonGui(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("JSON Pages Editor")
        self.geometry("900x580")

        self.current_file = None
        self.pages = []
        self.wrapper = None
        self.selected_index = None

        self.files = list_json_files(CATEGORIES_DIR)
        self.build_ui()

        if self.files:
            self.file_var.set(self.files[0])
            self.load_selected()
        else:
            messagebox.showwarning("No JSON files", f"No .json files found in:\n{CATEGORIES_DIR}")

    def build_ui(self):
        top = ttk.Frame(self, padding=10)
        top.pack(fill="x")

        ttk.Label(top, text="JSON file").pack(side="left")

        self.file_var = tk.StringVar(value=self.files[0] if self.files else "")
        self.file_combo = ttk.Combobox(
            top,
            textvariable=self.file_var,
            values=self.files,
            state="readonly",
            width=55,
        )
        self.file_combo.pack(side="left", padx=8)
        self.file_combo.bind("<<ComboboxSelected>>", lambda e: self.load_selected())

        ttk.Button(top, text="Reload list", command=self.reload_list).pack(side="left", padx=6)
        ttk.Button(top, text="Save", command=self.save_json).pack(side="left", padx=6)

        main = ttk.Frame(self, padding=10)
        main.pack(fill="both", expand=True)

        left = ttk.Frame(main)
        left.pack(side="left", fill="both", expand=True)

        right = ttk.Frame(main)
        right.pack(side="right", fill="y", padx=(12, 0))

        ttk.Label(left, text="Pages").pack(anchor="w")

        list_frame = ttk.Frame(left)
        list_frame.pack(fill="both", expand=True, pady=(6, 0))

        self.listbox = tk.Listbox(list_frame, height=22)
        self.listbox.pack(side="left", fill="both", expand=True)
        self.listbox.bind("<<ListboxSelect>>", lambda e: self.pick_from_list())

        scroll = ttk.Scrollbar(list_frame, orient="vertical", command=self.listbox.yview)
        scroll.pack(side="right", fill="y")
        self.listbox.config(yscrollcommand=scroll.set)

        form = ttk.LabelFrame(right, text="Template page", padding=10)
        form.pack(fill="x")

        ttk.Label(form, text="Title").grid(row=0, column=0, sticky="w")
        self.title_var = tk.StringVar()
        title_entry = ttk.Entry(form, textvariable=self.title_var, width=46)
        title_entry.grid(row=1, column=0, columnspan=2, sticky="we", pady=(0, 8))
        title_entry.bind("<KeyRelease>", self.on_title_change)

        ttk.Label(form, text="Id").grid(row=2, column=0, sticky="w")
        self.id_var = tk.StringVar()
        ttk.Entry(form, textvariable=self.id_var, width=46).grid(row=3, column=0, columnspan=2, sticky="we", pady=(0, 8))

        ttk.Label(form, text="Description").grid(row=4, column=0, sticky="w")
        self.desc_text = tk.Text(form, width=46, height=9, wrap="word")
        self.desc_text.grid(row=5, column=0, columnspan=2, sticky="we", pady=(0, 8))

        btn_row = ttk.Frame(form)
        btn_row.grid(row=6, column=0, columnspan=2, sticky="we")

        ttk.Button(btn_row, text="New", command=self.new_template).pack(side="left")
        ttk.Button(btn_row, text="Add", command=self.add_page).pack(side="left", padx=6)
        ttk.Button(btn_row, text="Update", command=self.update_page).pack(side="left", padx=6)
        ttk.Button(btn_row, text="Delete", command=self.delete_page).pack(side="left", padx=6)

        self.status_var = tk.StringVar(value="")
        ttk.Label(right, textvariable=self.status_var).pack(anchor="w", pady=(10, 0))

        self.new_template()

    def reload_list(self):
        self.files = list_json_files(CATEGORIES_DIR)
        self.file_combo["values"] = self.files
        if self.files:
            if self.file_var.get() not in self.files:
                self.file_var.set(self.files[0])
            self.load_selected()
        else:
            self.file_var.set("")
            self.current_file = None
            self.pages = []
            self.wrapper = None
            self.refresh_list()
            self.new_template()

    def selected_path(self):
        name = self.file_var.get().strip()
        if not name:
            return None
        return os.path.join(CATEGORIES_DIR, name)

    def load_selected(self):
        path = self.selected_path()
        if not path:
            return
        try:
            with open(path, "r", encoding="utf-8") as f:
                loaded = json.load(f)

            pages, wrapper = normalize_loaded_json(loaded)

            cleaned = []
            for it in pages:
                if isinstance(it, dict):
                    cleaned.append({
                        "id": str(it.get("id", "")).strip(),
                        "title": str(it.get("title", "")).strip(),
                        "description": str(it.get("description", "")).strip(),
                    })

            self.pages = cleaned
            self.wrapper = wrapper
            self.current_file = path
            self.selected_index = None
            self.refresh_list()
            self.new_template()
            self.set_status(f"Loaded {len(self.pages)} pages")
        except Exception as e:
            self.pages = []
            self.wrapper = None
            self.current_file = path
            self.selected_index = None
            self.refresh_list()
            self.new_template()
            messagebox.showerror("Load failed", f"Could not load JSON:\n{e}")

    def save_json(self):
        if not self.current_file:
            return
        try:
            if self.wrapper is None:
                payload = self.pages
            else:
                self.wrapper["pages"] = self.pages
                payload = self.wrapper

            with open(self.current_file, "w", encoding="utf-8") as f:
                json.dump(payload, f, ensure_ascii=False, indent=4)

            self.set_status("Saved")
            messagebox.showinfo("Saved", "JSON saved successfully.")
        except Exception as e:
            messagebox.showerror("Save failed", f"Could not save JSON:\n{e}")

    def refresh_list(self):
        self.listbox.delete(0, tk.END)
        for it in self.pages:
            label = it.get("title") or it.get("id") or "(empty)"
            self.listbox.insert(tk.END, label)

    def set_status(self, text):
        self.status_var.set(text)

    def on_title_change(self, event=None):
        pass

    def read_form(self):
        return {
            "id": self.id_var.get().strip(),
            "title": self.title_var.get().strip(),
            "description": self.desc_text.get("1.0", "end").strip(),
        }

    def write_form(self, it):
        self.title_var.set(it.get("title", ""))
        self.id_var.set(it.get("id", ""))
        self.desc_text.delete("1.0", "end")
        self.desc_text.insert("1.0", it.get("description", ""))

    def new_template(self):
        self.selected_index = None
        self.write_form(TEMPLATE.copy())
        self.set_status("New page")

    def find_duplicate_id(self, page_id, ignore_index=None):
        for idx, it in enumerate(self.pages):
            if ignore_index is not None and idx == ignore_index:
                continue
            if str(it.get("id", "")).strip() == page_id:
                return idx
        return None

    def add_page(self):
        it = self.read_form()
        if not it["id"] or not it["title"]:
            messagebox.showwarning("Missing fields", "Please fill Id and Title.")
            return

        dup = self.find_duplicate_id(it["id"])
        if dup is not None:
            messagebox.showerror("Duplicate id", f"A page with this id already exists at index {dup + 1}.")
            return

        # Insert at beginning
        self.pages.insert(0, it)

        self.refresh_list()
        self.listbox.selection_clear(0, tk.END)
        self.listbox.selection_set(0)
        self.listbox.see(0)
        self.selected_index = 0
        self.set_status(f"Added at top, total {len(self.pages)} pages")

    def update_page(self):
        if self.selected_index is None:
            messagebox.showwarning("Nothing selected", "Select a page to update.")
            return

        it = self.read_form()
        if not it["id"] or not it["title"]:
            messagebox.showwarning("Missing fields", "Please fill Id and Title.")
            return

        dup = self.find_duplicate_id(it["id"], ignore_index=self.selected_index)
        if dup is not None:
            messagebox.showerror("Duplicate id", f"Another page already uses this id at index {dup + 1}.")
            return

        self.pages[self.selected_index] = it
        self.refresh_list()
        self.listbox.selection_set(self.selected_index)
        self.set_status("Updated")

    def delete_page(self):
        if self.selected_index is None:
            messagebox.showwarning("Nothing selected", "Select a page to delete.")
            return

        idx = self.selected_index
        title = self.pages[idx].get("title") or self.pages[idx].get("id")
        if not messagebox.askyesno("Delete", f"Delete selected page?\n\n{title}"):
            return

        del self.pages[idx]
        self.selected_index = None
        self.refresh_list()
        self.new_template()
        self.set_status(f"Deleted, total {len(self.pages)} pages")

    def pick_from_list(self):
        sel = self.listbox.curselection()
        if not sel:
            return
        idx = int(sel[0])
        if idx < len(self.pages):
            self.selected_index = idx
            self.write_form(self.pages[idx])
            self.set_status(f"Selected page {idx + 1} of {len(self.pages)}")

if __name__ == "__main__":
    app = JsonGui()
    app.mainloop()