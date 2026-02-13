from PIL import Image
import os

root = "."

# loop through subfolders only (one level)
for entry in os.listdir(root):
    subpath = os.path.join(root, entry)

    if os.path.isdir(subpath):
        # process only PNG files directly inside each subfolder
        for filename in os.listdir(subpath):
            if filename.lower().endswith(".png"):
                filepath = os.path.join(subpath, filename)

                img = Image.open(filepath)

                # skip already converted images
                if img.mode == "1":
                    continue

                # convert to pure black and white
                gray = img.convert("L")
                bw = gray.point(lambda p: 255 if p >= 128 else 0, mode="1")

                # overwrite PNG
                bw.save(filepath, format="PNG", optimize=True)