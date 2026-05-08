(() => {
  // add click event listener to each simple calendar sidebar list item
  document.querySelectorAll("ul.simcal-events li").forEach((li) => {
    li.addEventListener("click", () => {
      li.classList.add("active");
    });
  });
})();
