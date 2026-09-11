/* =========================================================
   EventNow — script.js
   Convierte los <select class="filter-control"> en dropdowns
   personalizados (cs-select) manteniendo el select nativo
   oculto para formularios.
   ========================================================= */
(function () {
  function enhanceSelect(select) {
    var wrapper = document.createElement("div");
    wrapper.className = "cs-select";

    var trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = "cs-trigger";
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");
    trigger.textContent = select.options[select.selectedIndex].text;

    var panel = document.createElement("div");
    panel.className = "cs-panel";
    panel.setAttribute("role", "listbox");

    Array.prototype.forEach.call(select.options, function (option, index) {
      var item = document.createElement("div");
      item.className = "cs-option" + (index === select.selectedIndex ? " selected" : "");
      item.setAttribute("role", "option");
      item.setAttribute("aria-selected", index === select.selectedIndex ? "true" : "false");

      var label = document.createElement("span");
      label.textContent = option.text;
      var check = document.createElement("span");
      check.className = "cs-check";

      item.appendChild(label);
      item.appendChild(check);

      item.addEventListener("click", function (e) {
        e.stopPropagation();
        select.selectedIndex = index;
        select.dispatchEvent(new Event("change", { bubbles: true }));
        trigger.textContent = option.text;
        panel.querySelectorAll(".cs-option").forEach(function (el) {
          el.classList.remove("selected");
          el.setAttribute("aria-selected", "false");
        });
        item.classList.add("selected");
        item.setAttribute("aria-selected", "true");
        wrapper.classList.remove("open");
        trigger.setAttribute("aria-expanded", "false");
      });

      panel.appendChild(item);
    });

    trigger.addEventListener("click", function (e) {
      e.stopPropagation();
      var isOpen = wrapper.classList.contains("open");
      document.querySelectorAll(".cs-select.open").forEach(function (el) {
        el.classList.remove("open");
      });
      if (!isOpen) {
        wrapper.classList.add("open");
        trigger.setAttribute("aria-expanded", "true");
      }
    });

    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select); 
    wrapper.appendChild(trigger);
    wrapper.appendChild(panel);
  }

  document.addEventListener("click", function () {
    document.querySelectorAll(".cs-select.open").forEach(function (el) {
      el.classList.remove("open");
    });
  });

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("select.filter-control").forEach(enhanceSelect);
  });
})();
