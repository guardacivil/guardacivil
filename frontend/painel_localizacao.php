<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();
$currentUser = getCurrentUser();
$usuario_id = $currentUser['id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Rastreamento Pessoal</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        #map { height: 70vh; width: 100%; }
        .sidebar {
            position: fixed;
            top: 0; left: 0; height: 100vh; width: 240px;
            background: #1e3a8a;
            color: #fff;
            z-index: 2000;
            display: flex; flex-direction: column;
            padding: 2rem 1rem 1rem 1rem;
            box-shadow: 2px 0 8px rgba(0,0,0,0.12);
        }
        .sidebar h3 { font-size: 1.3rem; font-weight: bold; margin-bottom: 1.5rem; }
        .sidebar button, .sidebar a {
            width: 100%;
            margin-bottom: 1rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            text-align: left;
            display: flex; align-items: center;
            gap: 0.7rem;
            transition: background 0.2s;
        }
        .sidebar button:hover, .sidebar a:hover { background: #1e40af; }
        @media (max-width: 900px) {
            .sidebar { position: static; width: 100%; height: auto; flex-direction: row; padding: 0.5rem; box-shadow: none; }
            .sidebar button, .sidebar a { font-size: 0.95rem; padding: 0.5rem; margin-bottom: 0; margin-right: 0.5rem; }
        }
        .main-content { margin-left: 260px; transition: margin 0.2s; }
        @media (max-width: 900px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="sidebar">
        <h3><i class="fas fa-route mr-2"></i>Rastreamento</h3>
        <button onclick="centralizarUsuario()"><i class="fas fa-crosshairs"></i>Centralizar no Usuário</button>
        <button id="btn-alerta-menu"><i class="fas fa-bell"></i><span id="txt-alerta-menu">Ativar Alertas</span></button>
        <button onclick="exibirTrilha()"><i class="fas fa-shoe-prints"></i>Exibir Trilhas</button>
        <button onclick="configurarGeofence()"><i class="fas fa-draw-polygon"></i>Configurar Área</button>
        <button onclick="exportarTrilha()"><i class="fas fa-file-export"></i>Exportar Trilha</button>
        <a href="dashboard.php"><i class="fas fa-arrow-left"></i>Voltar ao Dashboard</a>
    </div>
    <main class="main-content max-w-3xl mx-auto bg-white p-8 mt-10 rounded shadow">
        <div class="mb-2 text-sm text-blue-700 bg-blue-100 rounded p-2">
            Este painel mostra sua posição atual e o histórico de deslocamento das últimas 2 horas.<br>
            <b>Alertas:</b> Você pode ativar alertas para ser notificado se sair de uma área ou ficar parado por muito tempo.
        </div>
        <div id="map"></div>
        <div id="alerta-msg" class="mt-4 text-red-700 font-bold"></div>
    </main>
    <script>
    let map = L.map('map').setView([-23.5, -47.6], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);
    let marker = null;
    let polyline = null;
    let alertaAtivo = false;
    let geofence = {lat: -23.5, lng: -47.6, raio: 2000}; // Exemplo: 2km do centro
    let tempoParado = 0;
    let ultimaPos = null;
    let alertaMsg = document.getElementById('alerta-msg');
    function buscarTrilha() {
        fetch('usuarios_localizacoes.php?usuario_id=<?=$usuario_id?>&historico=1')
            .then(r => r.json())
            .then(dados => {
                if (dados.length > 0) {
                    let latlngs = dados.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);
                    if (polyline) map.removeLayer(polyline);
                    polyline = L.polyline(latlngs, {color: 'blue'}).addTo(map);
                    let ultima = latlngs[latlngs.length-1];
                    if (marker) map.removeLayer(marker);
                    marker = L.marker(ultima).addTo(map).bindPopup('Você está aqui').openPopup();
                    map.setView(ultima, 16);
                    ultimaPos = ultima;
                    // Alertas
                    if (alertaAtivo) checarAlertas(latlngs);
                }
            });
    }
    function checarAlertas(latlngs) {
        let ultima = latlngs[latlngs.length-1];
        // Geofence
        let dist = map.distance(ultima, [geofence.lat, geofence.lng]);
        if (dist > geofence.raio) {
            alertaMsg.innerText = 'Alerta: Você saiu da área permitida!';
        } else {
            alertaMsg.innerText = '';
        }
        // Parado
        if (latlngs.length > 1) {
            let penultima = latlngs[latlngs.length-2];
            let d = map.distance(ultima, penultima);
            if (d < 10) tempoParado += 5; else tempoParado = 0;
            if (tempoParado >= 300) alertaMsg.innerText += '\nAlerta: Você está parado há mais de 5 minutos!';
        }
    }
    function centralizarUsuario() {
        if (ultimaPos) map.setView(ultimaPos, 16);
    }
    function exibirTrilha() {
        buscarTrilha();
    }
    function configurarGeofence() {
        let raio = prompt('Digite o raio da área permitida em metros:', geofence.raio);
        if (raio && !isNaN(raio)) {
            geofence.raio = parseInt(raio);
            alert('Área de geofence atualizada para ' + geofence.raio + ' metros.');
        }
    }
    function exportarTrilha() {
        fetch('usuarios_localizacoes.php?usuario_id=<?=$usuario_id?>&historico=1')
            .then(r => r.json())
            .then(dados => {
                let csv = 'latitude,longitude,atualizado_em\n';
                dados.forEach(p => { csv += `${p.latitude},${p.longitude},${p.atualizado_em}\n`; });
                let blob = new Blob([csv], {type: 'text/csv'});
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = 'trilha_usuario.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
    }
    document.getElementById('btn-alerta-menu').onclick = function() {
        alertaAtivo = !alertaAtivo;
        document.getElementById('txt-alerta-menu').innerText = alertaAtivo ? 'Desativar Alertas' : 'Ativar Alertas';
        document.getElementById('btn-alerta-menu').className = alertaAtivo ? 'bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded' : 'bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded';
        document.getElementById('alerta-status').innerText = alertaAtivo ? 'Alertas ativados' : 'Alertas desativados';
    };
    buscarTrilha();
    setInterval(buscarTrilha, 5000);
    </script>
</body>
</html> 