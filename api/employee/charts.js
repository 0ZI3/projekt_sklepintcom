/**
 * @fileoverview charts.js
 *
 * @description
 * Skrypt odpowiada za pobranie danych analitycznych i renderowanie wykresow
 * panelu administracyjnego po stronie klienta z wykorzystaniem biblioteki D3.
 * Obsluguje tworzenie wykresow, tooltipy, formatowanie danych i obsluge bledow.
 *
 * @scope
 * - Pobranie danych JSON z endpointu api/employee/charts_data.php.
 * - Renderowanie wykresow: sprzedaz, kategorie, top produkty i koszyki.
 * - Przygotowanie osi, legend, tooltipow oraz formatowania wartosci.
 * - Re-render wykresow po zmianie rozmiaru okna.
 * - Prezentacja komunikatu bledu przy problemach z ladowaniem danych.
 *
 * @behavior
 * - Inicjalizacja po zaladowaniu skryptu: automatyczne pobranie i narysowanie wykresow.
 * - Sukces pobrania: render wszystkich sekcji analitycznych.
 * - Blad pobrania/przetwarzania: wyswietlenie komunikatu w kontenerze bledow.
 */

(function () {
  "use strict";

  const CHART_COLORS = {
    indigo: "#4f46e5",
    rose: "#e11d48",
    slate: "#334155",
    teal: "#0f766e",
    amber: "#d97706",
    violet: "#7c3aed",
    sky: "#0284c7",
    emerald: "#059669",
  };

  const numberPL = new Intl.NumberFormat("pl-PL");
  const currencyPL = new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  });

  const errorNode = document.getElementById("charts-error");

  function showError(message) {
    if (!errorNode) {
      return;
    }
    errorNode.textContent = message;
    errorNode.classList.remove("hidden");
  }

  function hideError() {
    if (!errorNode) {
      return;
    }
    errorNode.classList.add("hidden");
  }

  function createSvg(containerId, minHeight, margin) {
    const container = document.getElementById(containerId);
    if (!container) {
      return null;
    }

    container.innerHTML = "";

    const width = Math.max(container.clientWidth || 320, 320);
    const height = Math.max(minHeight, 300);
    const innerWidth = width - margin.left - margin.right;
    const innerHeight = height - margin.top - margin.bottom;

    const svg = d3
      .select(container)
      .append("svg")
      .attr("width", width)
      .attr("height", height)
      .attr("viewBox", `0 0 ${width} ${height}`);

    const chart = svg
      .append("g")
      .attr("transform", `translate(${margin.left},${margin.top})`);

    return {
      container,
      svg,
      chart,
      width,
      height,
      innerWidth,
      innerHeight,
      margin,
    };
  }

  function ensureTooltip() {
    let tooltip = d3.select("#d3-tooltip");
    if (!tooltip.node()) {
      tooltip = d3
        .select("body")
        .append("div")
        .attr("id", "d3-tooltip")
        .style("position", "fixed")
        .style("pointer-events", "none")
        .style("z-index", "9999")
        .style("opacity", "0")
        .style("background", "rgba(15, 23, 42, 0.92)")
        .style("color", "#fff")
        .style("padding", "8px 10px")
        .style("border-radius", "8px")
        .style("font-size", "12px")
        .style("line-height", "1.35");
    }
    return tooltip;
  }

  function formatDateLabel(dateStr) {
    const date = new Date(`${dateStr}T00:00:00`);
    return date.toLocaleDateString("pl-PL");
  }

  function drawSalesLineChart(data) {
    const ctx = createSvg("sales-line-chart", 360, {
      top: 16,
      right: 60,
      bottom: 42,
      left: 60,
    });
    if (!ctx) {
      return;
    }

    const parsed = data
      .map((d) => ({
        dzien: d3.timeParse("%Y-%m-%d")(d.dzien),
        revenue: Number(d.przychod || 0),
        qty: Number(d.sprzedane_sztuki || 0),
        source: d,
      }))
      .filter(
        (d) => d.dzien instanceof Date && !Number.isNaN(d.dzien.getTime()),
      );

    const x = d3
      .scaleTime()
      .domain(d3.extent(parsed, (d) => d.dzien))
      .range([0, ctx.innerWidth]);

    const yRevenue = d3
      .scaleLinear()
      .domain([0, d3.max(parsed, (d) => d.revenue) || 1])
      .nice()
      .range([ctx.innerHeight, 0]);

    const yQty = d3
      .scaleLinear()
      .domain([0, d3.max(parsed, (d) => d.qty) || 1])
      .nice()
      .range([ctx.innerHeight, 0]);

    ctx.chart
      .append("g")
      .attr("transform", `translate(0,${ctx.innerHeight})`)
      .call(d3.axisBottom(x).ticks(8).tickFormat(d3.timeFormat("%d.%m")))
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    ctx.chart
      .append("g")
      .call(d3.axisLeft(yRevenue).ticks(6))
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    ctx.chart
      .append("g")
      .attr("transform", `translate(${ctx.innerWidth},0)`)
      .call(d3.axisRight(yQty).ticks(6))
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    const lineRevenue = d3
      .line()
      .x((d) => x(d.dzien))
      .y((d) => yRevenue(d.revenue));

    const lineQty = d3
      .line()
      .x((d) => x(d.dzien))
      .y((d) => yQty(d.qty));

    ctx.chart
      .append("path")
      .datum(parsed)
      .attr("fill", "none")
      .attr("stroke", CHART_COLORS.indigo)
      .attr("stroke-width", 2.4)
      .attr("d", lineRevenue);

    ctx.chart
      .append("path")
      .datum(parsed)
      .attr("fill", "none")
      .attr("stroke", CHART_COLORS.rose)
      .attr("stroke-width", 2.4)
      .attr("stroke-dasharray", "4 3")
      .attr("d", lineQty);

    const tooltip = ensureTooltip();

    ctx.chart
      .selectAll(".point-revenue")
      .data(parsed)
      .enter()
      .append("circle")
      .attr("cx", (d) => x(d.dzien))
      .attr("cy", (d) => yRevenue(d.revenue))
      .attr("r", 3)
      .attr("fill", CHART_COLORS.indigo)
      .on("mouseenter", function (_, d) {
        tooltip
          .style("opacity", "1")
          .html(
            `<b>${formatDateLabel(d.source.dzien)}</b><br>Przychod: ${currencyPL.format(d.revenue)}<br>Sprzedane sztuki: ${numberPL.format(d.qty)}`,
          );
        d3.select(this).attr("r", 4.5);
      })
      .on("mousemove", function (event) {
        tooltip
          .style("left", `${event.clientX + 12}px`)
          .style("top", `${event.clientY + 12}px`);
      })
      .on("mouseleave", function () {
        tooltip.style("opacity", "0");
        d3.select(this).attr("r", 3);
      });

    const legend = ctx.chart.append("g").attr("transform", "translate(0,-2)");
    legend
      .append("rect")
      .attr("x", 0)
      .attr("y", -12)
      .attr("width", 12)
      .attr("height", 3)
      .attr("fill", CHART_COLORS.indigo);
    legend
      .append("text")
      .attr("x", 16)
      .attr("y", -8)
      .style("font-size", "12px")
      .style("fill", "#334155")
      .text("Przychod");
    legend
      .append("rect")
      .attr("x", 94)
      .attr("y", -12)
      .attr("width", 12)
      .attr("height", 3)
      .attr("fill", CHART_COLORS.rose);
    legend
      .append("text")
      .attr("x", 112)
      .attr("y", -8)
      .style("font-size", "12px")
      .style("fill", "#334155")
      .text("Sztuki");
  }

  function drawCategoryPieChart(data) {
    const ctx = createSvg("category-pie-chart", 360, {
      top: 20,
      right: 20,
      bottom: 20,
      left: 20,
    });
    if (!ctx) {
      return;
    }

    const total = d3.sum(data, (d) => Number(d.przychod || 0));
    const radius = Math.min(ctx.innerWidth, ctx.innerHeight) / 2 - 8;

    const chartGroup = ctx.chart
      .append("g")
      .attr(
        "transform",
        `translate(${ctx.innerWidth / 2},${ctx.innerHeight / 2})`,
      );

    const color = d3
      .scaleOrdinal()
      .domain(data.map((d) => d.kategoria))
      .range([
        CHART_COLORS.indigo,
        CHART_COLORS.teal,
        CHART_COLORS.amber,
        CHART_COLORS.violet,
        CHART_COLORS.sky,
        CHART_COLORS.emerald,
        "#f97316",
        "#64748b",
        "#be123c",
        "#0ea5e9",
      ]);

    const pie = d3
      .pie()
      .value((d) => Number(d.przychod || 0))
      .sort(null);
    const arc = d3.arc().innerRadius(0).outerRadius(radius);

    const tooltip = ensureTooltip();

    chartGroup
      .selectAll("path")
      .data(pie(data))
      .enter()
      .append("path")
      .attr("d", arc)
      .attr("fill", (d) => color(d.data.kategoria))
      .attr("stroke", "#fff")
      .attr("stroke-width", 1.5)
      .on("mouseenter", function (_, d) {
        const share = total > 0 ? (d.data.przychod / total) * 100 : 0;
        tooltip
          .style("opacity", "1")
          .html(
            `<b>${d.data.kategoria}</b><br>Przychod: ${currencyPL.format(d.data.przychod)}<br>Udzial: ${share.toFixed(1)}%<br>Sztuki: ${numberPL.format(d.data.sprzedane_sztuki)}`,
          );
        d3.select(this).attr("opacity", 0.85);
      })
      .on("mousemove", function (event) {
        tooltip
          .style("left", `${event.clientX + 12}px`)
          .style("top", `${event.clientY + 12}px`);
      })
      .on("mouseleave", function () {
        tooltip.style("opacity", "0");
        d3.select(this).attr("opacity", 1);
      });

    const legend = ctx.chart.append("g").attr("transform", "translate(8,6)");
    const maxLegend = Math.min(data.length, 8);

    data.slice(0, maxLegend).forEach((item, idx) => {
      const share =
        total > 0 ? ((item.przychod / total) * 100).toFixed(1) : "0.0";
      const row = legend
        .append("g")
        .attr("transform", `translate(0,${idx * 18})`);
      row
        .append("rect")
        .attr("width", 11)
        .attr("height", 11)
        .attr("rx", 2)
        .attr("fill", color(item.kategoria));
      row
        .append("text")
        .attr("x", 16)
        .attr("y", 9)
        .style("font-size", "11px")
        .style("fill", "#334155")
        .text(`${item.kategoria} (${share}%)`);
    });
  }

  function drawTopProductsBarChart(data) {
    const ctx = createSvg("top-products-bar-chart", 420, {
      top: 16,
      right: 20,
      bottom: 48,
      left: 220,
    });
    if (!ctx) {
      return;
    }

    const top = [...data]
      .sort((a, b) => b.sprzedane_sztuki - a.sprzedane_sztuki)
      .slice(0, 10);

    const y = d3
      .scaleBand()
      .domain(top.map((d) => d.nazwa))
      .range([0, ctx.innerHeight])
      .padding(0.2);

    const x = d3
      .scaleLinear()
      .domain([0, d3.max(top, (d) => Number(d.sprzedane_sztuki || 0)) || 1])
      .nice()
      .range([0, ctx.innerWidth]);

    ctx.chart
      .append("g")
      .call(
        d3
          .axisLeft(y)
          .tickFormat((name) =>
            name.length > 28 ? `${name.slice(0, 28)}...` : name,
          ),
      )
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    ctx.chart
      .append("g")
      .attr("transform", `translate(0,${ctx.innerHeight})`)
      .call(d3.axisBottom(x).ticks(8))
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    const tooltip = ensureTooltip();

    ctx.chart
      .selectAll("rect.bar")
      .data(top)
      .enter()
      .append("rect")
      .attr("class", "bar")
      .attr("x", 0)
      .attr("y", (d) => y(d.nazwa))
      .attr("height", y.bandwidth())
      .attr("width", (d) => x(Number(d.sprzedane_sztuki || 0)))
      .attr("rx", 5)
      .attr("fill", CHART_COLORS.indigo)
      .on("mouseenter", function (_, d) {
        tooltip
          .style("opacity", "1")
          .html(
            `<b>${d.nazwa}</b><br>SKU: ${d.sku}<br>Sztuki: ${numberPL.format(d.sprzedane_sztuki)}<br>Przychod: ${currencyPL.format(d.przychod)}`,
          );
        d3.select(this).attr("opacity", 0.85);
      })
      .on("mousemove", function (event) {
        tooltip
          .style("left", `${event.clientX + 12}px`)
          .style("top", `${event.clientY + 12}px`);
      })
      .on("mouseleave", function () {
        tooltip.style("opacity", "0");
        d3.select(this).attr("opacity", 1);
      });

    ctx.chart
      .selectAll("text.bar-value")
      .data(top)
      .enter()
      .append("text")
      .attr("class", "bar-value")
      .attr("x", (d) => x(Number(d.sprzedane_sztuki || 0)) + 6)
      .attr("y", (d) => (y(d.nazwa) || 0) + y.bandwidth() / 2 + 4)
      .style("font-size", "11px")
      .style("fill", "#334155")
      .text((d) => `${d.sprzedane_sztuki}`);
  }

  function drawCartsLineChart(data) {
    const ctx = createSvg("carts-line-chart", 360, {
      top: 16,
      right: 24,
      bottom: 42,
      left: 54,
    });
    if (!ctx) {
      return;
    }

    const parsed = data
      .map((d) => ({
        dzien: d3.timeParse("%Y-%m-%d")(d.dzien),
        active: Number(d.aktywne_koszyki || 0),
        abandoned: Number(d.porzucone_koszyki || 0),
        source: d,
      }))
      .filter(
        (d) => d.dzien instanceof Date && !Number.isNaN(d.dzien.getTime()),
      );

    const x = d3
      .scaleTime()
      .domain(d3.extent(parsed, (d) => d.dzien))
      .range([0, ctx.innerWidth]);

    const y = d3
      .scaleLinear()
      .domain([0, d3.max(parsed, (d) => Math.max(d.active, d.abandoned)) || 1])
      .nice()
      .range([ctx.innerHeight, 0]);

    ctx.chart
      .append("g")
      .attr("transform", `translate(0,${ctx.innerHeight})`)
      .call(d3.axisBottom(x).ticks(8).tickFormat(d3.timeFormat("%d.%m")))
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    ctx.chart
      .append("g")
      .call(d3.axisLeft(y).ticks(6))
      .call((g) => g.selectAll("text").style("font-size", "11px"));

    const lineActive = d3
      .line()
      .x((d) => x(d.dzien))
      .y((d) => y(d.active));
    const lineAbandoned = d3
      .line()
      .x((d) => x(d.dzien))
      .y((d) => y(d.abandoned));

    ctx.chart
      .append("path")
      .datum(parsed)
      .attr("fill", "none")
      .attr("stroke", CHART_COLORS.teal)
      .attr("stroke-width", 2.4)
      .attr("d", lineActive);

    ctx.chart
      .append("path")
      .datum(parsed)
      .attr("fill", "none")
      .attr("stroke", CHART_COLORS.rose)
      .attr("stroke-width", 2.4)
      .attr("d", lineAbandoned);

    const tooltip = ensureTooltip();

    ctx.chart
      .selectAll(".point-cart")
      .data(parsed)
      .enter()
      .append("circle")
      .attr("cx", (d) => x(d.dzien))
      .attr("cy", (d) => y(Math.max(d.active, d.abandoned)))
      .attr("r", 3)
      .attr("fill", CHART_COLORS.slate)
      .on("mouseenter", function (_, d) {
        tooltip
          .style("opacity", "1")
          .html(
            `<b>${formatDateLabel(d.source.dzien)}</b><br>Aktywne: ${numberPL.format(d.active)}<br>Porzucone: ${numberPL.format(d.abandoned)}`,
          );
        d3.select(this).attr("r", 4.5);
      })
      .on("mousemove", function (event) {
        tooltip
          .style("left", `${event.clientX + 12}px`)
          .style("top", `${event.clientY + 12}px`);
      })
      .on("mouseleave", function () {
        tooltip.style("opacity", "0");
        d3.select(this).attr("r", 3);
      });

    const legend = ctx.chart.append("g").attr("transform", "translate(0,-2)");
    legend
      .append("rect")
      .attr("x", 0)
      .attr("y", -12)
      .attr("width", 12)
      .attr("height", 3)
      .attr("fill", CHART_COLORS.teal);
    legend
      .append("text")
      .attr("x", 16)
      .attr("y", -8)
      .style("font-size", "12px")
      .style("fill", "#334155")
      .text("Aktywne");
    legend
      .append("rect")
      .attr("x", 84)
      .attr("y", -12)
      .attr("width", 12)
      .attr("height", 3)
      .attr("fill", CHART_COLORS.rose);
    legend
      .append("text")
      .attr("x", 102)
      .attr("y", -8)
      .style("font-size", "12px")
      .style("fill", "#334155")
      .text("Porzucone");
  }

  function drawAll(payload) {
    drawSalesLineChart(payload.sales90 || []);
    drawCategoryPieChart(payload.categoryRevenue90 || []);
    drawTopProductsBarChart(payload.topProducts90 || []);
    drawCartsLineChart(payload.carts90 || []);
  }

  function loadCharts() {
    hideError();

    fetch("../../api/employee/charts_data.php", {
      headers: { Accept: "application/json" },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Nie udalo sie pobrac danych (${response.status}).`);
        }
        return response.json();
      })
      .then((payload) => {
        drawAll(payload);
      })
      .catch((error) => {
        showError(error.message || "Wystapil blad podczas ladowania wykresow.");
      });
  }

  window.addEventListener("resize", () => {
    loadCharts();
  });

  loadCharts();
})();
