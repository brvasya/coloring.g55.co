(function () {
  function getRandomColor() {
    let color;
    do {
      color = Math.floor(Math.random() * 0xffffff)
        .toString(16)
        .padStart(6, "0");
    } while (color.toLowerCase() === "ffffff");
    return "#" + color;
  }

  function colorize() {
    document.querySelectorAll(".thumbnail:not([data-colored])").forEach((el) => {
      el.style.borderColor = getRandomColor();
      el.dataset.colored = "1";
    });

    document.querySelectorAll(".tag:not([data-colored])").forEach((el) => {
      el.style.backgroundColor = getRandomColor();
      el.dataset.colored = "1";
    });
  }

  function start() {
    colorize();

    const obs = new MutationObserver(colorize);
    obs.observe(document.documentElement, {
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
