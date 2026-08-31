(function () {
  'use strict';

  if (typeof Chart === 'undefined') {
    console.error('Chart.js failed to load.');
    return;
  }

  const palette = ['rgb(0,169,242)', '#f9b234', '#06b6d4', '#10b981'];

  function readJson(canvas, key) {
    try {
      return JSON.parse(canvas.dataset[key] || '[]');
    } catch (error) {
      console.error(`Invalid chart ${key} data.`, error);
      return [];
    }
  }

  function formatValue(value, unit) {
    const number = Number(value) || 0;
    const formatted = new Intl.NumberFormat('vi-VN').format(number);
    return unit === 'đ' ? `${formatted} đ` : `${formatted} ${unit}`.trim();
  }

  function showEmptyState(canvas) {
    canvas.hidden = true;
    const emptyState = document.createElement('p');
    emptyState.className = 'chart-empty-state';
    emptyState.textContent = 'Chưa có dữ liệu để hiển thị.';
    canvas.parentElement.appendChild(emptyState);
  }

  function createChart(canvas) {
    const labels = readJson(canvas, 'labels');
    const values = readJson(canvas, 'values').map(Number);
    const type = canvas.dataset.chart;
    const unit = canvas.dataset.unit || '';
    const total = values.reduce((sum, value) => sum + (Number.isFinite(value) ? value : 0), 0);

    if ((type === 'doughnut' && total <= 0) || values.length === 0) {
      showEmptyState(canvas);
      return;
    }

    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
      existingChart.destroy();
    }

    const configuredColor = canvas.dataset.color || palette[0];
    const isDoughnut = type === 'doughnut';
    const colors = isDoughnut
      ? values.map((_, index) => palette[index % palette.length])
      : configuredColor;
    const label = type === 'bar' ? 'Doanh thu' : type === 'line' ? 'Đơn hàng' : '';

    new Chart(canvas, {
      type,
      data: {
        labels,
        datasets: [{
          label,
          data: values,
          backgroundColor: colors,
          borderColor: isDoughnut ? '#ffffff' : configuredColor,
          borderWidth: 2,
          borderRadius: type === 'bar' ? 6 : 0,
          fill: false,
          tension: type === 'line' ? 0.25 : 0,
          pointRadius: type === 'line' ? 3 : 0,
          pointHoverRadius: type === 'line' ? 5 : 0,
          pointBackgroundColor: configuredColor
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500 },
        plugins: {
          legend: {
            display: isDoughnut,
            position: 'bottom'
          },
          tooltip: {
            callbacks: {
              label(context) {
                const value = context.parsed.y ?? context.parsed;
                return `${context.label}: ${formatValue(value, unit)}`;
              }
            }
          }
        },
        scales: isDoughnut ? {} : {
          x: {
            grid: { color: 'rgba(17, 24, 39, 0.08)' },
            ticks: { color: '#6b7280' }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(17, 24, 39, 0.08)' },
            ticks: {
              color: '#6b7280',
              callback(value) {
                return formatValue(value, unit);
              }
            }
          }
        }
      }
    });
  }

  function renderAdminCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
      if (!canvas.hidden) createChart(canvas);
    });
  }

  document.addEventListener('DOMContentLoaded', renderAdminCharts);
})();
