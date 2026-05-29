(function () {
  // Group lookup functionality
  const form = document.getElementById("group-lookup");
  const resultsContainer = document.getElementById("group-lookup-results");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const search = form.search.value.trim();

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

      if (groups.length === 0) {
        resultsContainer.innerHTML = cnca.group_lookup.no_results;
        return;
      }

      resultsContainer.innerHTML = groups
        .map((group) => {
          const highlightedText = group.name.replace(
            new RegExp(search, "gi"),
            (match) => `<mark>${match}</mark>`,
          );
          return `
                <article>
                    <h3>${highlightedText}</h3>
                    <dl>
                        <dt>${cnca.group_lookup.location}</dt>
                        <dd>${group.city}</dd>
                        <dt>${cnca.group_lookup.district}</dt>
                        <dd>${group.district}</dd>
                        <dt>${cnca.group_lookup.id}</dt>
                        <dd>${group.id || "-"}</dd>
                    </dl>
                </article>
            `;
        })
        .join("");
    } catch (error) {
      console.error(error);
      resultsContainer.innerHTML = cnca.group_lookup.error;
    }
  });
})();
