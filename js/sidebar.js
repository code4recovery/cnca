(() => {
  // add click event listener to each simple calendar sidebar list item
  document.querySelectorAll("ul.simcal-events li").forEach((li) => {
    li.addEventListener("click", () => {
      const details = li.querySelector(".details");
      li.classList.toggle("active");
      const options = {
        duration: 300,
        easing: "ease-in-out",
        fill: "forwards",
      };
      const inStyle = { opacity: 0, maxHeight: 0, marginTop: 0 };
      const outStyle = {
        opacity: 1,
        maxHeight: `${details.scrollHeight}px`,
        marginTop: 8,
      };
      if (li.classList.contains("active")) {
        details.animate([inStyle, outStyle], options).onfinish = () => {
          details.style.maxHeight = "none";
        };
      } else {
        details.animate([outStyle, inStyle], options).onfinish = () => {
          details.style.maxHeight = 0;
        };
      }
    });
  });

  // add click event listener to each simple calendar sidebar list item link to prevent event bubbling
  document.querySelectorAll("ul.simcal-events a").forEach((a) => {
    a.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  });
})();
