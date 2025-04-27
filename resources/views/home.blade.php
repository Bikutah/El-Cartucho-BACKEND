@extends('layouts.app')
@section('title', 'El Cartucho')
@section('content')
<div class="row">
    <!-- Area Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Ventas Mensuales (Area Chart)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- Pie Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Distribución de Productos (Pie Chart)</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="myPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <!-- Bar Chart -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Comparativa Anual (Bar Chart)</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="myBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Doughnut Chart -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Categorias (Doughnut Chart)</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="myDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Area Chart
    var ctxArea = document.getElementById("myAreaChart").getContext('2d');
    new Chart(ctxArea, {
        type: 'line',
        data: {
            labels: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio"],
            datasets: [{
                label: "Ventas",
                data: [0, 10000, 5000, 15000, 10000, 20000],
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
            }],
        },
        options: { maintainAspectRatio: false }
    });

    // Pie Chart
    var ctxPie = document.getElementById("myPieChart").getContext('2d');
    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ["Perifericos", "Indumentaria", "Consolas"],
            datasets: [{
                data: [55, 30, 15],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
            }],
        },
        options: { maintainAspectRatio: false }
    });

    // Bar Chart
    var ctxBar = document.getElementById("myBarChart").getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ["2018", "2019", "2020", "2021", "2022", "2023"],
            datasets: [{
                label: "Ventas",
                backgroundColor: "#4e73df",
                hoverBackgroundColor: "#2e59d9",
                data: [4215, 5312, 6251, 7841, 9821, 14984],
            }],
        },
        options: { maintainAspectRatio: false }
    });

    // Doughnut Chart
    var ctxDoughnut = document.getElementById("myDoughnutChart").getContext('2d');
    new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: ["Perifericos", "Indumentaria", "Consolas"],
            datasets: [{
                data: [60, 30, 10],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
            }],
        },
        options: { maintainAspectRatio: false }
    });
});
</script>
@endpush
