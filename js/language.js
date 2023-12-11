submissionLocales = new Map(submissionLocales);

window.addEventListener("load", (_) => {
  let pkpTabs = document.querySelector(".pkpTabs");

  // User's queue is preloaded on component initialization.
  // It needs to be modified separately.
  for (let listElement of document.querySelectorAll(".listPanel__item")) {
    addLanguageInfo(listElement);
  }
  
  // Watch for changes in listPanels and modify dynamically loaded submissions.
  function tabsObserverCallback(mutationList, _) {
    for (const mutation of mutationList) {
      if (mutation.addedNodes.length == 0) {
        continue;
      }

      let addedEl = mutation.addedNodes[0];

      if (addedEl.className == "listPanel__itemsList") {
        for (let listElement of addedEl.children) {
          addLanguageInfo(listElement);
        }
      }

      if (addedEl.className == "listPanel__item" && !addedEl.querySelector(".listPanel__itemSubtitle--submissionTitle")) {
        addLanguageInfo(addedEl);
      }
    }
  }
  
  let observer = new MutationObserver(tabsObserverCallback);
  observer.observe(pkpTabs, { attributes: false, childList: true, subtree: true });
});

function addLanguageInfo(listElement) {
  let submissionEntry = listElement.querySelector(".listPanel__itemIdentity--submission");
  let id = parseInt(submissionEntry.firstElementChild.textContent.trim(), 10);

  let titleElement = submissionEntry.querySelector(".listPanel__itemSubtitle");
  let formattedLocale = localeToLanguageCode(submissionLocales.get(id));

  let langSpan = document.createElement("span");
  let titleSpan = document.createElement("span");

  // Plugin setting received from the server.
  if (useCountryFlags) {
    langSpan.classList.add("listPanel__itemSubtitle--submissionLanguageFlag");
    langSpan.textContent = localeToFlag(formattedLocale);
  } else {
    langSpan.classList.add("listPanel__itemSubtitle--submissionLanguage");
    langSpan.textContent = formattedLocale;
  }

  titleSpan.classList.add("listPanel__itemSubtitle--submissionTitle");
  titleSpan.textContent = titleElement.textContent;
  titleElement.textContent = "";

  titleElement.appendChild(langSpan);
  titleElement.appendChild(titleSpan);
}

// Given a locale string like "en_US" or "en"
// return ISO 639-1 language code (e.g. "EN") un uppercase.
function localeToLanguageCode(locale) {
  let underscoreIndex = locale.indexOf("_");

  if (underscoreIndex > 0) {
    return locale.slice(0, underscoreIndex).toUpperCase();
  }

  return locale.toUpperCase();
}

function localeToFlag(languageCode) {
  // Some languages are not associated with a single country flag,
  // and need to be assigned manually.
  let specialCases = new Map([["EN", "GB"]]);

  let specialCase = specialCases.get(languageCode);

  if (specialCase) {
    languageCode = specialCase;
  }

  return String.fromCodePoint(
    languageCode.codePointAt(0) + 127397,
    languageCode.codePointAt(1) + 127397
  );
}
