// notificacoes_realtime.js
setInterval(function() {
    fetch('backend/api/alertas.php')
        .then(r => r.json())
        .then(data => {
            if (data && data.novas) {
                // Exemplo: exibir notificação do navegador
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Nova notificação', { body: data.novas[0].titulo });
                }
            }
        });
}, 30000); 