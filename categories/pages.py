import json
import re
from pathlib import Path

for source in Path.cwd().iterdir():
    if source.is_file() and source.suffix.lower() == ".json":
        values = re.findall(r'"id"\s*:\s*("(?:\\.|[^"\\])*")', source.read_text(encoding="utf-8-sig"))
        text = "".join(json.loads(value).removesuffix("-coloring-page").replace("-", " ").strip() + "\n" for value in values)
        source.with_suffix(".txt").write_text(text, encoding="utf-8")
