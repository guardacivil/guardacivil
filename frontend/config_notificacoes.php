<?php
// config_notificacoes.php - Configuração do Sistema de Notificações
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Processar formulário de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $configuracoes = [
            'alertas_seguranca' => isset($_POST['alertas_seguranca']) ? 1 : 0,
            'relatorios_pendentes' => isset($_POST['relatorios_pendentes']) ? 1 : 0,
            'comunicacoes_urgentes' => isset($_POST['comunicacoes_urgentes']) ? 1 : 0,
            'atualizacoes_sistema' => isset($_POST['atualizacoes_sistema']) ? 1 : 0,
            'frequencia' => $_POST['frequencia'] ?? 'imediato',
            'email_notificacoes' => isset($_POST['email_notificacoes']) ? 1 : 0,
            'push_notificacoes' => isset($_POST['push_notificacoes']) ? 1 : 0,
            'limite_ocorrencias' => $_POST['limite_ocorrencias'] ?? 10,
            'limite_pessoal' => $_POST['limite_pessoal'] ?? 20,
            'usuario_id' => $currentUser['id']
        ];

        // Salvar configurações (implementar lógica de salvamento)
        $_SESSION['notificacoes_config'] = $configuracoes;
        
        $mensagem = "Configurações salvas com sucesso!";
        $tipo_mensagem = "success";
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar configurações: " . $e->getMessage();
        $tipo_mensagem = "error";
    }
}

// Carregar configurações atuais
$configuracoes = $_SESSION['notificacoes_config'] ?? [
    'alertas_seguranca' => 1,
    'relatorios_pendentes' => 1,
    'comunicacoes_urgentes' => 1,
    'atualizacoes_sistema' => 0,
    'frequencia' => 'imediato',
    'email_notificacoes' => 1,
    'push_notificacoes' => 1,
    'limite_ocorrencias' => 10,
    'limite_pessoal' => 20
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Configurar Notificações - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
        }
        .config-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #3b82f6;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <img src="img/logo1.png" alt="Logo" class="w-8 h-8 mr-3" />
                    <h1 class="text-xl font-semibold text-gray-900">Configurar Notificações</h1>
                </div>
                <a href="notificacoes.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                </a>
            </div>
        </div>
    </header>

    <!-- Conteúdo principal -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if (isset($mensagem)): ?>
        <div class="mb-6 p-4 rounded-lg <?= $tipo_mensagem === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <i class="fas fa-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
            <?= htmlspecialchars($mensagem) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            
            <!-- Tipos de Notificação -->
            <div class="config-card">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Tipos de Notificação</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800">Alertas de Segurança</h3>
                            <p class="text-sm text-gray-600">Notificações sobre ocorrências críticas e alertas de segurança</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="alertas_seguranca" <?= $configuracoes['alertas_seguranca'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800">Relatórios Pendentes</h3>
                            <p class="text-sm text-gray-600">Lembretes sobre relatórios que precisam ser gerados</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="relatorios_pendentes" <?= $configuracoes['relatorios_pendentes'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800">Comunicações Urgentes</h3>
                            <p class="text-sm text-gray-600">Notificações sobre comunicações marcadas como urgentes</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="comunicacoes_urgentes" <?= $configuracoes['comunicacoes_urgentes'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800">Atualizações do Sistema</h3>
                            <p class="text-sm text-gray-600">Notificações sobre atualizações e manutenções do sistema</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="atualizacoes_sistema" <?= $configuracoes['atualizacoes_sistema'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Frequência de Notificações -->
            <div class="config-card">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Frequência de Notificações</h2>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="radio" name="frequencia" value="imediato" <?= $configuracoes['frequencia'] === 'imediato' ? 'checked' : '' ?> class="mr-3">
                        <span>Imediato - Receber notificações assim que ocorrerem</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="frequencia" value="hora" <?= $configuracoes['frequencia'] === 'hora' ? 'checked' : '' ?> class="mr-3">
                        <span>A cada hora - Resumo das notificações a cada hora</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="frequencia" value="diario" <?= $configuracoes['frequencia'] === 'diario' ? 'checked' : '' ?> class="mr-3">
                        <span>Diário - Resumo diário das notificações</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="frequencia" value="semanal" <?= $configuracoes['frequencia'] === 'semanal' ? 'checked' : '' ?> class="mr-3">
                        <span>Semanal - Resumo semanal das notificações</span>
                    </label>
                </div>
            </div>

            <!-- Canais de Notificação -->
            <div class="config-card">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Canais de Notificação</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800">E-mail</h3>
                            <p class="text-sm text-gray-600">Receber notificações por e-mail</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="email_notificacoes" <?= $configuracoes['email_notificacoes'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800">Notificações Push</h3>
                            <p class="text-sm text-gray-600">Receber notificações no navegador</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="push_notificacoes" <?= $configuracoes['push_notificacoes'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Limites de Alertas -->
            <div class="config-card">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Limites de Alertas</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Limite de Ocorrências por Dia
                        </label>
                        <input type="number" name="limite_ocorrencias" 
                               value="<?= $configuracoes['limite_ocorrencias'] ?>" 
                               min="1" max="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-600 mt-1">Alerta quando exceder este número</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Limite Mínimo de Pessoal
                        </label>
                        <input type="number" name="limite_pessoal" 
                               value="<?= $configuracoes['limite_pessoal'] ?>" 
                               min="1" max="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-600 mt-1">Alerta quando ficar abaixo deste número</p>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-4">
                <button type="button" onclick="testarNotificacoes()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-bell mr-2"></i>Testar Notificações
                </button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Salvar Configurações
                </button>
            </div>
        </form>

        <!-- Preview de Notificações -->
        <div class="config-card mt-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Preview de Notificações</h2>
            <div class="space-y-4">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Alerta de Segurança:</strong> 15 ocorrências registradas hoje, acima do limite configurado.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Relatório Pendente:</strong> Relatório semanal precisa ser gerado até sexta-feira.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bell text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Comunicação Urgente:</strong> Nova comunicação urgente do Comandante.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function testarNotificacoes() {
            // Testar notificação do navegador
            if ('Notification' in window) {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        new Notification('Sistema Integrado da Guarda Civil - Teste', {
                            body: 'Esta é uma notificação de teste do sistema',
                            icon: '/img/logo1.png',
                            badge: '/img/logo1.png'
                        });
                        alert('Notificação de teste enviada!');
                    } else {
                        alert('Permissão de notificação negada. Verifique as configurações do navegador.');
                    }
                });
            } else {
                alert('Notificações não são suportadas neste navegador.');
            }
        }

        // Salvar configurações automaticamente ao alterar
        document.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(input => {
            input.addEventListener('change', function() {
                console.log('Configuração alterada:', this.name, this.value || this.checked);
            });
        });

        // Validar limites
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', function() {
                const value = parseInt(this.value);
                const min = parseInt(this.min);
                const max = parseInt(this.max);
                
                if (value < min) this.value = min;
                if (value > max) this.value = max;
            });
        });
    </script>
</body>
</html> 