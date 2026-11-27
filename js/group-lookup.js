(function () {
  // Group lookup functionality
  const form = document.getElementById("group-lookup");
  const resultsContainer = document.getElementById("group-lookup-results");
  const submitButton = form.querySelector('input[type="submit"]');
  const searchInput = form.elements.search;
  const fieldSelect = form.elements.field;
  const searchQueryParams = {
    group_name: "group_name",
    city: "group_city",
    location: "group_location",
    address: "group_address",
    district: "group_district",
    group_id: "group_id",
    fnv: "group_fnv",
  };
  const legacySearchQueryParams = {
    group_lookup_name: "group_name",
    group_lookup_city: "city",
    group_lookup_location: "location",
    group_lookup_address: "address",
    group_lookup_district: "district",
    group_lookup_id: "group_id",
    group_lookup_fnv: "fnv",
    name: "group_name",
    city: "city",
    location: "location",
    address: "address",
    district: "district",
    id: "group_id",
    fnv: "fnv",
  };
  const maxSearchLength = cnca.group_lookup.max_search_length;
  const isSpanish = document.documentElement.lang.toLowerCase().startsWith("es");
  const spanishStatuses = new Map([
    ["Unknown", "Desconocido"],
    ["Active", "Activo"],
    ["Inactive", "Inactivo"],
    ["Never Registered", "Nunca registrado"],
  ]);

  function displayValue(value) {
    if (Array.isArray(value)) {
      return value.map(displayValue).filter(Boolean).join(", ");
    }

    if (typeof value !== "string" && typeof value !== "number" && typeof value !== "boolean") {
      return "";
    }

    return String(value).trim();
  }

  function displayTypeFormat(value) {
    const formattedValue = displayValue(value);
    const separatorIndex = formattedValue.indexOf("/");

    if (separatorIndex === -1) return formattedValue;

    return isSpanish
      ? formattedValue.slice(separatorIndex + 1).trim()
      : formattedValue.slice(0, separatorIndex).trim();
  }

  function displayStatus(value) {
    const status = displayValue(value);
    return isSpanish ? spanishStatuses.get(status) ?? status : status;
  }

  function appendHighlightedText(element, text, search) {
    const regex = new RegExp(search.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "gi");
    let lastIndex = 0;

    text.replace(regex, (match, offset) => {
      element.append(document.createTextNode(text.slice(lastIndex, offset)));
      const mark = document.createElement("mark");
      mark.textContent = match;
      element.append(mark);
      lastIndex = offset + match.length;
      return match;
    });
    element.append(document.createTextNode(text.slice(lastIndex)));
  }

  searchInput.addEventListener("input", () => {
    searchInput.setCustomValidity("");
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    searchGroups(true);
  });

  async function searchGroups(updateUrl) {
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
    if (updateUrl) {
      const searchParam = searchQueryParams[fieldSelect.value];
      if (searchParam) {
        const url = new URL(window.location.href);
        Object.values(searchQueryParams).forEach((param) => url.searchParams.delete(param));
        Object.keys(legacySearchQueryParams).forEach((param) => url.searchParams.delete(param));
        url.searchParams.delete("group_lookup_search");
        url.searchParams.delete("group_lookup_field");
        url.searchParams.set(searchParam, search);
        if (url.href !== window.location.href) {
          window.history.pushState(null, "", url);
        }
      }
    }

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
        credentials: "omit",
      });

      if (!response.ok) throw new Error("Network response was not ok");

      const result = await response.json();
      if (
        !result ||
        !Array.isArray(result.groups) ||
        (result.refreshed_ago !== null && typeof result.refreshed_ago !== "string")
      ) {
        throw new Error("Invalid group lookup response");
      }

      const groups = result.groups;
      const refreshedMessage = document.createElement("p");
      refreshedMessage.className = "group-lookup-refresh";
      refreshedMessage.textContent =
        result.refreshed_ago === null
          ? cnca.group_lookup.refresh_time_unavailable
          : cnca.group_lookup.refreshed_ago.replace("%s", result.refreshed_ago);

      if (groups.length === 0) {
        const noResultsMessage = document.createElement("p");
        noResultsMessage.textContent = cnca.group_lookup.no_results;
        resultsContainer.replaceChildren(noResultsMessage, refreshedMessage);
        return;
      }

      const articles = groups.map((group) => {
        const article = document.createElement("article");
        const name = displayValue(group.name);

        if (name) {
          const heading = document.createElement("h3");
          if (fieldSelect.value === "group_name") {
            appendHighlightedText(heading, name, search);
          } else {
            heading.textContent = name;
          }
          article.append(heading);
        }

        const details = document.createElement("dl");
        [
          [cnca.group_lookup.id, group.id, "group_id"],
          [cnca.group_lookup.fnv, group.fnv, "fnv"],
          [cnca.group_lookup.district, group.district, "district"],
          [cnca.group_lookup.sub_district, group.sub_district],
          [cnca.group_lookup.location, group.location, "location"],
          [cnca.group_lookup.type_format, displayTypeFormat(group.type_format)],
          [cnca.group_lookup.address, group.address, "address"],
          [cnca.group_lookup.city, group.city, "city"],
          [cnca.group_lookup.status, displayStatus(group.status)],
        ].forEach(([label, rawValue, searchField]) => {
          const value = displayValue(rawValue);
          if (!value) return;

          const term = document.createElement("dt");
          term.textContent = label;
          const description = document.createElement("dd");
          if (fieldSelect.value === searchField) {
            appendHighlightedText(description, value, search);
          } else {
            description.textContent = value;
          }
          details.append(term, description);
        });
        if (details.childElementCount) article.append(details);
        return article;
      });

      resultsContainer.replaceChildren(...articles, refreshedMessage);
    } catch (error) {
      console.error(error);
      resultsContainer.textContent = cnca.group_lookup.error;
    } finally {
      delete form.dataset.loading;
      form.removeAttribute("aria-busy");
      submitButton.disabled = false;
    }
  }

  function restoreSearchFromUrl() {
    const url = new URL(window.location.href);
    const searchParam = Object.entries(searchQueryParams).find(([, param]) =>
      url.searchParams.has(param)
    );
    const legacySearchParam = searchParam
      ? null
      : Object.entries(legacySearchQueryParams).find(([param]) => url.searchParams.has(param));
    let search = null;
    let field = "group_name";
    let legacySearch = false;

    if (searchParam) {
      [field] = searchParam;
      search = url.searchParams.get(searchParam[1]);
    } else if (legacySearchParam) {
      [field] = legacySearchParam;
      field = legacySearchQueryParams[field];
      search = url.searchParams.get(legacySearchParam[0]);
      legacySearch = true;
    } else {
      search = url.searchParams.get("group_lookup_search");
      if (search !== null) {
        const legacyField = url.searchParams.get("group_lookup_field") || "group_name";
        field = Object.prototype.hasOwnProperty.call(searchQueryParams, legacyField)
          ? legacyField
          : "group_name";
        legacySearch = true;
      }
    }

    if (search === null) {
      form.reset();
      searchInput.setCustomValidity("");
      resultsContainer.replaceChildren();
      return;
    }

    searchInput.value = search;
    fieldSelect.value = field;
    if (legacySearch) {
      const cleanUrl = new URL(window.location.href);
      Object.values(searchQueryParams).forEach((param) => cleanUrl.searchParams.delete(param));
      Object.keys(legacySearchQueryParams).forEach((param) => cleanUrl.searchParams.delete(param));
      cleanUrl.searchParams.delete("group_lookup_search");
      cleanUrl.searchParams.delete("group_lookup_field");
      cleanUrl.searchParams.set(searchQueryParams[field], search.trim());
      window.history.replaceState(null, "", cleanUrl);
    }
    searchGroups(false);
  }

  window.addEventListener("popstate", restoreSearchFromUrl);
  restoreSearchFromUrl();
})();
