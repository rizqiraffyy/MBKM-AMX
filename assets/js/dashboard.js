(function($) {
  'use strict';
  $(function() {
    if ($("#performanceLine").length) { 
      const ctx = document.getElementById('performanceLine');
      var graphGradient = document.getElementById("performanceLine").getContext('2d');

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ["12/26/2003","01/26/2004", "02/26/2004", "03/26/2004", "04/26/2004","05/26/2004", "06/26/2004", "07/26/2004", "08/26/2004", "09/26/2004", "10/26/2004", "11/26/2004"],
          datasets: [
            {
              label: 'Average Health',
              data: [50, 110, 60, 290, 200, 115, 130, 170, 90, 210, 240, 280, 200],
              backgroundColor: 'rgba(31, 59, 179, 0.2)', // Biru transparan
              borderColor: '#1F3BB3', // Biru
              borderWidth: 1.5,
              fill: true,
              pointBorderWidth: 1,
              pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
              pointHoverRadius: [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
              pointBackgroundColor: '#1F3BB3',
              pointBorderColor: '#fff',
            },
            {
              label: 'Average Weight',
              data: [50, 110, 60, 290, 200, 115, 130, 170, 90, 210, 240, 280, 200],
              backgroundColor: 'rgba(255, 99, 132, 0.2)', // Merah transparan
              borderColor: '#FF6384', // Merah
              borderWidth: 1.5,
              fill: true,
              pointBorderWidth: 1,
              pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
              pointHoverRadius: [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
              pointBackgroundColor: '#FF6384',
              pointBorderColor: '#fff',
            },
            {
              label: 'Weight Gain',
              data: [50, 110, 60, 290, 200, 115, 130, 170, 90, 210, 240, 280, 200],
              backgroundColor: 'rgba(75, 192, 192, 0.2)', // Hijau transparan
              borderColor: '#4BC0C0', // Hijau
              borderWidth: 1.5,
              fill: true,
              pointBorderWidth: 1,
              pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
              pointHoverRadius: [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
              pointBackgroundColor: '#4BC0C0',
              pointBorderColor: '#fff',
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          elements: {
            line: {
                tension: 0.4,
            }
          },
          scales: {
            y: {
              grid: {
                display: true,
                color: "#F0F0F0",
                drawBorder: false,
              },
              ticks: {
                beginAtZero: false,
                autoSkip: true,
                maxTicksLimit: 4,
                color: "#6B778C",
                font: {
                  size: 10,
                }
              }
            },
            x: {
              grid: {
                display: false,
                drawBorder: false,
              },
              ticks: {
                beginAtZero: false,
                autoSkip: true,
                maxTicksLimit: 7,
                color: "#6B778C",
                font: {
                  size: 10,
                }
              }
            }
          },
          plugins: {
            legend: {
                display: true,
                labels: {
                    color: "#6B778C"
                }
            }
          }
        }
      });
    }
  });
})(jQuery);
