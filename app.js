async function loadData() {
  const res = await fetch("pages.json", { cache: "no-store" });
  if (!res.ok) throw new Error("pages.json not found");
  return res.json();
}

function el(html) {
  const t = document.createElement("template");
  t.innerHTML = html.trim();
  return t.content.firstChild;
}

function qs(name) {
  return new URLSearchParams(location.search).get(name);
}

function setMetaDescription(text) {
  const meta = document.querySelector('meta[name="description"]');
  if (meta) meta.setAttribute("content", text || "");
}

function setLink(rel, href) {
  const link = document.querySelector(`link[rel="${rel}"]`);
  if (link) link.setAttribute("href", href || "");
}

function withCountPrefix(count, title) {
  return count > 0 ? `${count} ${title}` : title;
}

/* menu */

function renderMenu(data) {
  const menu = document.getElementById("category-menu");
  if (!menu) return;

  menu.innerHTML = "";
  data.categories.forEach(c => {
    menu.appendChild(el(`
      <li>
        <a class="tag"
           href="index.html?c=${encodeURIComponent(c.id)}"
           title="${c.name}"
           target="_top">${c.name}</a>
      </li>
    `));
  });
}

/* grid */

function renderGrid(pages) {
  const grid = document.getElementById("pages-grid");
  if (!grid) return;

  grid.innerHTML = "";
  pages.forEach(p => {
    grid.appendChild(el(`
      <a class="thumbnail"
         href="page.html?id=${encodeURIComponent(p.id)}"
         title="${p.title}"
         target="_top">
        <img loading="lazy"
             src="${p.image}"
             alt="${p.title}"
             width="170"
             height="128">
      </a>
    `));
  });
}

/* home and category */

async function initRoot() {
  const data = await loadData();

  const cid = qs("c");
  const isCategory = !!cid;

  renderMenu(data);

  const h1El = document.getElementById("h1");
  const descEl = document.getElementById("desc");

  if (!isCategory) {
    const totalCount = data.pages.length;

    document.title = data.site?.title || "";
    if (h1El) {
      h1El.textContent = withCountPrefix(
        totalCount,
        data.site?.h1 || ""
      );
    }
    if (descEl) descEl.textContent = data.site?.description || "";

    setMetaDescription(data.site?.description || "");
    setLink("canonical", "/");

    const latest = [...data.pages].slice(-24).reverse();
    renderGrid(latest);
    return;
  }

  const cat = data.categories.find(x => x.id === cid);
  const catName = cat?.name || "Category";
  const catDesc = cat?.description || data.site?.description || "";

  const categoryCount = data.pages.filter(p => p.category === cid).length;

  document.title = catName + (data.site?.title ? " | " + data.site.title : "");
  if (h1El) {
    h1El.textContent = withCountPrefix(
      categoryCount,
      catName
    );
  }
  if (descEl) descEl.textContent = catDesc;

  setMetaDescription(catDesc);
  setLink("canonical", "/?c=" + encodeURIComponent(cid));

  const list = data.pages.filter(p => p.category === cid);
  renderGrid(list);
}

/* single page */

async function initPage() {
  const data = await loadData();
  const id = qs("id");

  const page = data.pages.find(p => p.id === id);
  if (!page) return;

  const category = data.categories.find(c => c.id === page.category);

  document.title = page.title + (data.site?.title ? " | " + data.site.title : "");
  setMetaDescription(page.description || page.title);
  setLink("canonical", "/page.html?id=" + encodeURIComponent(page.id));
  setLink("image_src", page.image);

  const h1 = document.getElementById("page-h1");
  const desc = document.getElementById("page-desc");

  if (h1) h1.textContent = page.title;
  if (desc) {
    desc.textContent =
      page.description ||
      category?.description ||
      data.site?.description ||
      "";
  }

  const img = document.getElementById("printable");
  if (img) {
    img.src = page.image;
    img.alt = page.description;
  }

  const dl = document.getElementById("download");
  if (dl) dl.href = page.image;

  const more = document.getElementById("more-link");
  if (more && category) {
    more.textContent = "More " + category.name;
    more.href = "index.html?c=" + encodeURIComponent(category.id);
  }

  const moreTitle = document.getElementById("more-title");
  if (moreTitle && category) {
    moreTitle.textContent =
      "Similar Free Printable " + category.name + " You May Like";
  }

  const similar = data.pages
    .filter(p => p.category === page.category && p.id !== page.id)
    .slice(0, 8);

  const list = document.getElementById("more-pages-list");
  if (list) {
    list.innerHTML = "";
    similar.forEach(p => {
      list.appendChild(el(`
        <a class="thumbnail"
           href="page.html?id=${encodeURIComponent(p.id)}"
           title="${p.title}"
           target="_top">
          <img loading="lazy"
               src="${p.image}"
               alt="${p.title}"
               width="170"
               height="128">
        </a>
      `));
    });
  }

  renderMenu(data);
}

/* boot */

document.addEventListener("DOMContentLoaded", () => {
  const pageType = document.body.dataset.page;

  if (pageType === "root") {
    initRoot().catch(console.error);
  }

  if (pageType === "page") {
    initPage().catch(console.error);
  }
});
