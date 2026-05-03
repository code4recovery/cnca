const btn = document.querySelector("#cnca-menu-toggle");
const menu = document.querySelector("#cnca-primary-navigation > ul");

btn.addEventListener("click", () => {
  btn.classList.toggle("active");
  menu.classList.toggle("active");
});
