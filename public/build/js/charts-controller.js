import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
  static targets = ['pdvStatusChart', 'transactionStatusChart', 'userRoleChart', 'revenueChart', 'transactionsTimelineChart'];
  static values = {
    pdvByStatus: Object,
    transactionsByStatus: Object,
    usersByRole: Object,
    revenueByPdv: Object,
    transactionsByDay: Array,
    dayLabels: Array,
  };

  connect() {
    this.initializeCharts();
  }

  initializeCharts() {
    if (this.hasPdvStatusChartTarget) {
      this.createPdvStatusChart();
    }
    if (this.hasTransactionStatusChartTarget) {
      this.createTransactionStatusChart();
    }
    if (this.hasUserRoleChartTarget) {
      this.createUserRoleChart();
    }
    if (this.hasRevenueChartTarget) {
      this.createRevenueChart();
    }
    if (this.hasTransactionsTimelineChartTarget) {
      this.createTransactionsTimelineChart();
    }
  }

  createPdvStatusChart() {
    const ctx = this.pdvStatusChartTarget.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Actif', 'Fermé', 'Suspendu'],
        datasets: [{
          data: [
            this.pdvByStatusValue.ACTIF || 0,
            this.pdvByStatusValue.FERME || 0,
            this.pdvByStatusValue.SUSPENDU || 0,
          ],
          backgroundColor: [
            '#16a34a',
            '#dc2626',
            '#ea580c',
          ],
          borderColor: '#fff',
          borderWidth: 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: { size: 12 },
              padding: 15,
            },
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.label + ': ' + context.parsed + ' PDV';
              },
            },
          },
        },
      },
    });
  }

  createTransactionStatusChart() {
    const ctx = this.transactionStatusChartTarget.getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['En attente', 'Validée', 'Rejetée'],
        datasets: [{
          label: 'Nombre de transactions',
          data: [
            this.transactionsByStatusValue.EN_ATTENTE || 0,
            this.transactionsByStatusValue.VALIDEE || 0,
            this.transactionsByStatusValue.REJETEE || 0,
          ],
          backgroundColor: [
            '#f59e0b',
            '#16a34a',
            '#dc2626',
          ],
          borderColor: '#1e40af',
          borderWidth: 1,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
            },
          },
        },
      },
    });
  }

  createUserRoleChart() {
    const ctx = this.userRoleChartTarget.getContext('2d');
    new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Admin', 'Agent', 'Gérant'],
        datasets: [{
          label: 'Nombre d\'utilisateurs',
          data: [
            this.usersByRoleValue.ADMIN || 0,
            this.usersByRoleValue.AGENT || 0,
            this.usersByRoleValue.GERANT || 0,
          ],
          borderColor: '#1e40af',
          backgroundColor: 'rgba(30, 64, 175, 0.1)',
          borderWidth: 2,
          pointRadius: 5,
          pointBackgroundColor: '#1e40af',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
        scales: {
          r: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
            },
          },
        },
      },
    });
  }

  createRevenueChart() {
    const ctx = this.revenueChartTarget.getContext('2d');
    const labels = Object.keys(this.revenueByPdvValue).slice(0, 8);
    const data = Object.values(this.revenueByPdvValue).slice(0, 8);

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Revenu (FCFA)',
          data: data,
          backgroundColor: '#1e40af',
          borderColor: '#1e3a8a',
          borderWidth: 1,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          x: {
            beginAtZero: true,
          },
        },
      },
    });
  }

  createTransactionsTimelineChart() {
    const ctx = this.transactionsTimelineChartTarget.getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: this.dayLabelsValue,
        datasets: [{
          label: 'Transactions par jour',
          data: this.transactionsByDayValue,
          borderColor: '#1e40af',
          backgroundColor: 'rgba(30, 64, 175, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 5,
          pointBackgroundColor: '#1e40af',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
            },
          },
        },
      },
    });
  }
}
