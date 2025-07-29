<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';
if (!isAdminLoggedIn() && $perfil !== 'Administrador') {
    echo '<p>Você não tem permissão para acessar este mapa.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Usuários em Tempo Real</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        #map { height: 80vh; width: 100%; }
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
    <?php include 'sidebar.php'; ?>
    <main class="main-content max-w-5xl mx-auto bg-white p-8 mt-10 rounded shadow" style="margin-left: 16rem;">
        <div class="mb-4 flex items-center">
            <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors mr-4">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <h2 class="text-2xl font-bold mb-0 flex items-center"><i class="fas fa-map-marker-alt mr-2"></i>Localização em Tempo Real dos Usuários</h2>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            <button onclick="escolherUsuarioCentralizar()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2"><i class="fas fa-crosshairs"></i>Centralizar em Usuário</button>
            <button onclick="atualizarLocalizacoes(true)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2"><i class="fas fa-sync"></i>Atualizar Mapa</button>
            <button onclick="exportarLocalizacoes()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2"><i class="fas fa-file-export"></i>Exportar Lista</button>
            <button onclick="configurarGeofence()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2"><i class="fas fa-draw-polygon"></i>Configurar Área</button>
            <button onclick="limparGeofence()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2"><i class="fas fa-eraser"></i>Limpar Área</button>
            <button onclick="abrirModalHistorico()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2"><i class="fas fa-route"></i>Histórico de Itinerário</button>
        </div>
        <div class="mb-2 text-sm text-blue-700 bg-blue-100 rounded p-2">
            Para visualizar os usuários no mapa, cada usuário precisa permitir o acesso à localização no navegador.<br>
            A atualização é automática a cada 5 segundos.
        </div>
        <div id="map" style="height: 80vh; width: 100%;"></div>
    </main>
    <div id="modalHistorico" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:3000; align-items:center; justify-content:center;">
      <div style="background:#fff; padding:2rem; border-radius:1rem; min-width:320px; max-width:90vw;">
        <h3 class="text-xl font-bold mb-4">Histórico de Itinerário</h3>
        <label class="block mb-2">Selecione o usuário:</label>
        <select id="selectUsuarioHistorico" class="w-full border rounded px-3 py-2 mb-4"></select>
        <label class="block mb-2">Período (horas atrás):</label>
        <input type="number" id="periodoHistorico" class="w-full border rounded px-3 py-2 mb-4" min="1" max="24" value="2">
        <div class="flex justify-end gap-2">
          <button onclick="fecharModalHistorico()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">Cancelar</button>
          <button onclick="limparHistoricoUsuario()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Limpar Histórico</button>
          <button onclick="buscarItinerarioUsuario()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Buscar</button>
        </div>
      </div>
    </div>
    <script>
    let map = L.map('map').setView([-23.5, -47.6], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);
    let markers = {};
    let usuariosCache = [];
    let geofence = {lat: -23.5, lng: -47.6, raio: 2000};
    let geofenceCircle = null;
    // Cores distintas para marcadores
    const markerColors = [
        'red', 'blue', 'green', 'orange', 'purple', 'darkred', 'cadetblue', 'darkgreen', 'darkblue', 'darkpurple', 'pink', 'gray', 'black', 'lightblue', 'lightgreen', 'lightred', 'beige', 'yellow', 'lightgray'
    ];
    function getColoredIcon(color) {
        return new L.Icon({
            iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
    }
    function atualizarLocalizacoes(showMsg = true) {
        fetch('./usuarios_localizacoes.php') // Caminho relativo ao diretório do arquivo
            .then(r => r.json())
            .then(usuarios => {
                console.log(usuarios); // Depuração: exibe os dados recebidos
                usuariosCache = usuarios;
                for (let id in markers) {
                    map.removeLayer(markers[id]);
                }
                markers = {};
                if (usuarios.length === 0) {
                    if (!window._noUserMsg && showMsg) {
                        window._noUserMsg = L.popup({closeButton:false, autoClose:false})
                            .setLatLng(map.getCenter())
                            .setContent('<b>Nenhum usuário logado visível no momento.</b>')
                            .openOn(map);
                    }
                    return;
                } else if (window._noUserMsg) {
                    map.closePopup(window._noUserMsg);
                    window._noUserMsg = null;
                }
                if (usuarios[0].latitude && usuarios[0].longitude) {
                    map.setView([parseFloat(usuarios[0].latitude), parseFloat(usuarios[0].longitude)], 15);
                }
                usuarios.forEach((u, idx) => {
                    if (u.latitude && u.longitude) {
                        let lat = parseFloat(u.latitude);
                        let lng = parseFloat(u.longitude);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            let color = markerColors[idx % markerColors.length];
                            let marker = L.marker([lat, lng], {icon: getColoredIcon(color)})
                                .addTo(map)
                                .bindPopup(`<b>${u.nome || 'Usuário'}</b><br><span style='font-size:12px'>${u.perfil_nome || ''}</span><br>Latitude: ${lat}<br>Longitude: ${lng}<br><small>Atualizado: ${u.atualizado_em || ''}</small>`);
                            markers[u.usuario_id] = marker;
                        }
                    }
                });
                desenharGeofence();
            });
    }
    function escolherUsuarioCentralizar() {
        if (!usuariosCache.length) { alert('Nenhum usuário disponível para centralizar.'); return; }
        let nomes = usuariosCache.map((u, i) => `${i+1} - ${u.nome || 'Usuário'} (${u.perfil_nome || ''})`).join('\n');
        let escolha = prompt('Escolha o número do usuário para centralizar:\n' + nomes);
        let idx = parseInt(escolha) - 1;
        if (!isNaN(idx) && usuariosCache[idx] && usuariosCache[idx].latitude && usuariosCache[idx].longitude) {
            let u = usuariosCache[idx];
            map.setView([parseFloat(u.latitude), parseFloat(u.longitude)], 16);
            if (markers[u.usuario_id]) markers[u.usuario_id].openPopup();
        } else if (escolha !== null) {
            alert('Escolha inválida.');
        }
    }
    function exportarLocalizacoes() {
        fetch('./frontend/usuarios_localizacoes.php')
            .then(r => r.json())
            .then(usuarios => {
                if (!usuarios.length) { alert('Nenhuma localização para exportar.'); return; }
                let csv = 'nome,perfil,latitude,longitude,atualizado_em\n';
                usuarios.forEach(u => { csv += `${u.nome},${u.perfil_nome},${u.latitude},${u.longitude},${u.atualizado_em}\n`; });
                let blob = new Blob([csv], {type: 'text/csv'});
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = 'localizacoes_usuarios.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
    }
    function configurarGeofence() {
        let lat = geofence.lat, lng = geofence.lng, raio = geofence.raio;
        let novoRaio = prompt('Digite o raio da área permitida em metros:', raio);
        if (novoRaio && !isNaN(novoRaio)) {
            geofence.raio = parseInt(novoRaio);
        }
        let novaLat = prompt('Digite a latitude do centro da área:', lat);
        let novoLng = prompt('Digite a longitude do centro da área:', lng);
        if (novaLat && !isNaN(novaLat)) geofence.lat = parseFloat(novaLat);
        if (novoLng && !isNaN(novoLng)) geofence.lng = parseFloat(novoLng);
        desenharGeofence();
        alert('Área de geofence atualizada!');
    }
    function limparGeofence() {
        geofence = {lat: null, lng: null, raio: null};
        if (geofenceCircle) { map.removeLayer(geofenceCircle); geofenceCircle = null; }
        alert('Área de geofence removida!');
    }
    function desenharGeofence() {
        if (geofenceCircle) map.removeLayer(geofenceCircle);
        if (geofence && geofence.lat && geofence.lng && geofence.raio) {
            geofenceCircle = L.circle([geofence.lat, geofence.lng], {
                color: '#f59e42', fillColor: '#fbbf24', fillOpacity: 0.2, radius: geofence.raio
            }).addTo(map);
        }
    }
    function abrirModalHistorico() {
      document.getElementById('modalHistorico').style.display = 'flex';
      carregarUsuariosHistorico();
    }
    function fecharModalHistorico() {
      document.getElementById('modalHistorico').style.display = 'none';
    }
    function carregarUsuariosHistorico() {
      fetch('usuarios.php?json=1')
        .then(r => r.json())
        .then(usuarios => {
          let select = document.getElementById('selectUsuarioHistorico');
          select.innerHTML = '';
          usuarios.forEach(u => {
            let opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.nome + (u.perfil ? ' ('+u.perfil+')' : '');
            select.appendChild(opt);
          });
        });
    }
    function buscarItinerarioUsuario() {
      const usuarioId = document.getElementById('selectUsuarioHistorico').value;
      const periodo = parseInt(document.getElementById('periodoHistorico').value) || 2;
      if (!usuarioId) { alert('Selecione um usuário!'); return; }
      fetch(`usuarios_localizacoes.php?usuario_id=${usuarioId}&historico=1&periodo=${periodo}`)
        .then(r => r.json())
        .then(dados => {
          fecharModalHistorico();
          if (!dados.length) {
            alert('Nenhum dado de itinerário encontrado para o período selecionado.');
            return;
          }
          // Remove polyline e marcadores anteriores se existirem
          if (window.itinerarioPolyline) map.removeLayer(window.itinerarioPolyline);
          if (window.itinerarioMarkers) window.itinerarioMarkers.forEach(m => map.removeLayer(m));
          window.itinerarioMarkers = [];
          let latlngs = dados.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);
          window.itinerarioPolyline = L.polyline(latlngs, {color: 'red', weight: 4}).addTo(map);
          dados.forEach((p, idx) => {
            let marker = L.marker([parseFloat(p.latitude), parseFloat(p.longitude)], {icon: getColoredIcon('red')})
              .addTo(map)
              .bindPopup(`<b>Ponto ${idx+1}</b><br>Latitude: ${p.latitude}<br>Longitude: ${p.longitude}<br><small>${p.atualizado_em}</small>`);
            window.itinerarioMarkers.push(marker);
          });
          map.fitBounds(window.itinerarioPolyline.getBounds());
        });
    }
    function limparHistoricoUsuario() {
      const usuarioId = document.getElementById('selectUsuarioHistorico').value;
      const periodo = parseInt(document.getElementById('periodoHistorico').value) || 2;
      if (!usuarioId) { alert('Selecione um usuário!'); return; }
      if (!confirm('Tem certeza que deseja limpar o histórico deste usuário para o período selecionado?')) return;
      fetch(`usuarios_localizacoes.php?usuario_id=${usuarioId}&limpar=1&periodo=${periodo}`, { method: 'POST' })
        .then(r => r.json())
        .then(resp => {
          if (resp.success) {
            alert('Histórico limpo com sucesso!');
            buscarItinerarioUsuario();
          } else {
            alert('Erro ao limpar histórico: ' + (resp.error || 'Erro desconhecido.'));
          }
        })
        .catch(() => alert('Erro ao limpar histórico.'));
    }
    // Botões do menu lateral
    document.querySelector('.sidebar button[onclick*="escolherUsuarioCentralizar"]').title = 'Escolha um usuário para centralizar o mapa';
    document.querySelector('.sidebar button[onclick*="atualizarLocalizacoes"]').title = 'Atualiza manualmente as localizações';
    document.querySelector('.sidebar button[onclick*="exportarLocalizacoes"]').title = 'Exporta a lista de localizações em CSV';
    document.querySelector('.sidebar button[onclick*="configurarGeofence"]').title = 'Configura a área de geofence e exibe no mapa';
    document.querySelector('.sidebar button[onclick*="limparGeofence"]').title = 'Remove a área de geofence do mapa';
    document.querySelector('.sidebar button[onclick*="abrirModalHistorico"]').title = 'Abre o modal para buscar histórico de itinerário';
    // Remover funções e elementos relacionados ao dropdown e modal de programação de atualização do mapa
    </script>
</body>
</html> 