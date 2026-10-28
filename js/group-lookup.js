(function () {
  // Group lookup functionality
  const form = document.getElementById("group-lookup");
  const resultsContainer = document.getElementById("group-lookup-results");
  const submitButton = form.querySelector('input[type="submit"]');
  const searchInput = form.elements.search;
  const maxSearchLength = cnca.group_lookup.max_search_length;

  searchInput.addEventListener("input", () => {
    searchInput.setCustomValidity("");
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (form.dataset.loading === "true") return;

    const search = searchInput.value.trim();
    if (!search) {
      searchInput.setCustomValidity(cnca.group_lookup.empty_search);
      searchInput.reportValidity();
      return;
    }

    if (search.length > maxSearchLength) {
      searchInput.setCustomValidity(cnca.group_lookup.search_too_long);
      searchInput.reportValidity();
      return;
    }

    searchInput.setCustomValidity("");
    const loadingMessage = document.createElement("p");
    loadingMessage.className = "group-lookup-loading";
    loadingMessage.setAttribute("role", "status");
    loadingMessage.textContent = cnca.group_lookup.loading;

    form.dataset.loading = "true";
    form.setAttribute("aria-busy", "true");
    submitButton.disabled = true;
    resultsContainer.replaceChildren(loadingMessage);

    try {
      const response = await fetch(`${cnca.root}cnca/v1/group-lookup`, {
        method: "POST",
        body: new FormData(form),
        headers: {
          "X-WP-Nonce": cnca.nonce,
        },
      });

      if (!response.ok) throw new Error("Network response was not ok");

      const groups = await response.json();
      if (!Array.isArray(groups)) throw new Error("Invalid group lookup response");

      if (groups.length === 0) {
        resultsContainer.textContent = cnca.group_lookup.no_results;
        return;
      }

      const regex = new RegExp(search.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "gi");
      const articles = groups.map((group) => {
        const article = document.createElement("article");
        const heading = document.createElement("h3");
        const name = String(group.name ?? "");
        let lastIndex = 0;

        name.replace(regex, (match, offset) => {
          heading.append(document.createTextNode(name.slice(lastIndex, offset)));
          const mark = document.createElement("mark");
          mark.textContent = match;
          heading.append(mark);
          lastIndex = offset + match.length;
          return match;
        });
        heading.append(document.createTextNode(name.slice(lastIndex)));
        article.append(heading);

        const details = document.createElement("dl");
        [
          [cnca.group_lookup.location, group.city],
          [cnca.group_lookup.district, group.district],
          [cnca.group_lookup.id, group.id || "-"],
        ].forEach(([label, value]) => {
          const term = document.createElement("dt");
          term.textContent = label;
          const description = document.createElement("dd");
          description.textContent = String(value ?? "");
          details.append(term, description);
        });
        article.append(details);
        return article;
      });

      resultsContainer.replaceChildren(...articles);
    } catch (error) {
      console.error(error);
      resultsContainer.textContent = cnca.group_lookup.error;
    } finally {
      delete form.dataset.loading;
      form.removeAttribute("aria-busy");
      submitButton.disabled = false;
    }
  });
})();
