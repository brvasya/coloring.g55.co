# generate_sitemap.py
# Reads:
# - pages.json (root) for site.baseUrl and categories
# - categories/<category_id>.json for pages in each category
# Writes:
# - sitemap.xml (root)
#
# Run on Windows:
# py generate_sitemap.py

import json
import datetime
from pathlib import Path
from urllib.parse import quote

ROOT = Path(__file__).resolve().parent

def norm_base(base: str) -> str:
  base = (base or "").strip()
  if not base:
    raise ValueError("Missing site.baseUrl in pages.json (example: https://coloring.g55.co)")
  return base[:-1] if base.endswith("/") else base

def read_json(path: Path):
  with path.open("r", encoding="utf-8") as f:
    return json.load(f)

def q(value: str) -> str:
  return quote(str(value), safe="")

def main():
  pages_index_path = ROOT / "pages.json"
  if not pages_index_path.exists():
    raise FileNotFoundError("pages.json not found in root")

  index = read_json(pages_index_path)
  base = norm_base(index.get("site", {}).get("baseUrl", ""))
  today = datetime.date.today().isoformat()

  categories = index.get("categories", [])
  if not isinstance(categories, list):
    categories = []

  urls = []
  seen = set()

  def add_url(loc: str):
    if loc in seen:
      return
    seen.add(loc)
    urls.append((loc, today))

  # Home
  add_url(f"{base}/")

  # Categories and pages inside each category file
  for c in categories:
    cid = c.get("id")
    if not cid:
      continue

    # Category URL
    add_url(f"{base}/?c={q(cid)}")

    # Category pages file
    cat_path = ROOT / "categories" / f"{cid}.json"
    if not cat_path.exists():
      # If a category file is missing, skip it (still keep the category URL)
      continue

    cat_data = read_json(cat_path)
    pages = cat_data.get("pages", [])
    if not isinstance(pages, list):
      continue

    # Page URLs include both id and c (matches your links and canonical)
    for p in pages:
      pid = p.get("id")
      if not pid:
        continue
      add_url(f"{base}/page.html?id={q(pid)}&c={q(cid)}")

  xml_lines = []
  xml_lines.append('<?xml version="1.0" encoding="UTF-8"?>')
  xml_lines.append('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')

  for loc, lastmod in urls:
    xml_lines.append("  <url>")
    xml_lines.append(f"    <loc>{loc}</loc>")
    xml_lines.append(f"    <lastmod>{lastmod}</lastmod>")
    xml_lines.append("  </url>")

  xml_lines.append("</urlset>")
  xml = "\n".join(xml_lines) + "\n"

  out_path = ROOT / "sitemap.xml"
  out_path.write_text(xml, encoding="utf-8")

  print(f"Generated sitemap.xml with {len(urls)} URLs")

if __name__ == "__main__":
  main()
