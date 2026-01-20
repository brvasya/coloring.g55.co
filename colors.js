(function () {
  function hslToHex(h, s, l) {
    s /= 100;
    l /= 100;

    const c = (1 - Math.abs(2 * l - 1)) * s;
    const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
    const m = l - c / 2;

    let r = 0, g = 0, b = 0;

    if (h < 60) { r = c; g = x; b = 0; }
    else if (h < 120) { r = x; g = c; b = 0; }
    else if (h < 180) { r = 0; g = c; b = x; }
    else if (h < 240) { r = 0; g = x; b = c; }
    else if (h < 300) { r = x; g = 0; b = c; }
    else { r = c; g = 0; b = x; }

    const toHex = v =>
      Math.round((v + m) * 255).toString(16).padStart(2, "0");

    return "#" + toHex(r) + toHex(g) + toHex(b);
  }

  function getReadablePastel() {
    const h = Math.floor(Math.random() * 360);
    const s = 55 + Math.floor(Math.random() * 15);
    const l = 45 + Math.floor(Math.random() * 15);
    return hslToHex(h, s, l);
  }

  function colorize() {
    document.querySelectorAll(".thumbnail:not([data-colored])").forEach(el => {
      el.style.borderColor = getReadablePastel();
      el.dataset.colored = "1";
    });

    document.querySelectorAll(".tag:not([data-colored])").forEach(el => {
      el.style.backgroundColor = getReadablePastel();
      el.dataset.colored = "1";
    });
  }

  function start() {
    colorize();

    new MutationObserver(colorize).observe(document.documentElement, {
      childList: true,
      subtree: true
    });

    setTimeout(colorize, 250);
    setTimeout(colorize, 1000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
