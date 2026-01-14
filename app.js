async function loadData() {
  const res = await fetch("pages.json", { cache: "no-store" });
  if (!res.ok) throw new Error("pages.json not found");
  return res.json();
}

async function loadCategoryPages(cid) {
  const res = await fetch(`categories/${encodeURIComponent(cid)}.json`, { cache: "no-store" });
  if (!res.ok) throw new Error("Category file not found: " + cid);
  const data = await res.json();
  return Array.isArray(data.pages) ? data.pages : [];
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

/* menu (alphabetical) */

function renderMenu(data) {
  const menu = document.getElementById("category-menu");
  if (!menu) return;

  menu.innerHTML = "";

  const sortedCategories = [...(data.categories || [])].sort((a, b) =>
    (a.name || "").localeCompare(b.name || "")
  );

  sortedCategories.forEach(c => {
    menu.appendChild(el(`
      <li>
        <a class="tag"
           href="index.html?c=${encodeURIComponent(c.id)}"
           title="${c.name || ""}"
           target="_top">${c.name || ""}</a>
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
         href="page.html?id=${encodeURIComponent(p.id)}&c=${encodeURIComponent(p.category)}"
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

/* home + category */

async function initRoot() {
  const data = await loadData();

  const cid = qs("c");
  const isCategory = !!cid;

  renderMenu(data);

  const h1El = document.getElementById("h1");
  const descEl = document.getElementById("desc");

  if (!isCategory) {
    // load pages from all categories (for latest 24)
    let allPages = [];
    for (const c of (data.categories || [])) {
      const pages = await loadCategoryPages(c.id);
      pages.forEach(p => allPages.push({ ...p, category: c.id }));
    }

    const totalCount = allPages.length;

    document.title = data.site?.title || "";
    if (h1El) h1El.textContent = withCountPrefix(totalCount, data.site?.h1 || "");
    if (descEl) descEl.textContent = data.site?.description || "";

    setMetaDescription(data.site?.description || "");
    setLink("canonical", "/");

    const latest = allPages.slice(-24).reverse();
    renderGrid(latest);
    return;
  }

  const cat = (data.categories || []).find(x => x.id === cid);
  const catName = cat?.name || "Category";
  const catDesc = cat?.description || data.site?.description || "";

  const pages = (await loadCategoryPages(cid)).map(p => ({ ...p, category: cid }));
  const categoryCount = pages.length;

  document.title = catName + (data.site?.title ? " | " + data.site.title : "");
  if (h1El) h1El.textContent = withCountPrefix(categoryCount, catName);
  if (descEl) descEl.textContent = catDesc;

  setMetaDescription(catDesc);
  setLink("canonical", "/?c=" + encodeURIComponent(cid));

  renderGrid(pages.slice().reverse());
}

/* single page */

async function initPage() {
  const data = await loadData();

  const id = qs("id");
  let cid = qs("c"); // optional but helps performance

  // If category not provided, find it by scanning categories
  if (!cid) {
    for (const c of (data.categories || [])) {
      const pages = await loadCategoryPages(c.id);
      if (pages.some(p => p.id === id)) {
        cid = c.id;
        break;
      }
    }
  }

  if (!cid) return;

  const pages = await loadCategoryPages(cid);
  const page = pages.find(p => p.id === id);
  if (!page) return;

  const category = (data.categories || []).find(c => c.id === cid);

  document.title = page.title + (data.site?.title ? " | " + data.site.title : "");
  setMetaDescription(page.description || page.title);
  setLink("canonical", "/page.html?id=" + encodeURIComponent(id) + "&c=" + encodeURIComponent(cid));
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
    more.href = "index.html?c=" + encodeURIComponent(cid);
  }

  const moreTitle = document.getElementById("more-title");
  if (moreTitle && category) {
    moreTitle.textContent = "Similar Free Printable " + category.name + " You May Like";
  }

  const similar = pages
    .filter(p => p.id !== id)
    .slice()
    .reverse()
    .slice(0, 8)
    .map(p => ({ ...p, category: cid }));

  const list = document.getElementById("more-pages-list");
  if (list) {
    list.innerHTML = "";
    similar.forEach(p => {
      list.appendChild(el(`
        <a class="thumbnail"
           href="page.html?id=${encodeURIComponent(p.id)}&c=${encodeURIComponent(p.category)}"
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
