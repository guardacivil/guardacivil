// fiveserver.config.js - Configuração do Five Server
module.exports = {
  php: "C:\\xampp\\php\\php.exe",   // Caminho do PHP no Windows com XAMPP
  port: 5500,                       // Porta padrão
  open: true,                       // Abrir navegador automaticamente
  root: "./frontend",               // Pasta raiz do projeto
  host: "localhost",                // Host local
  index: "index.php",               // Arquivo inicial
  mount: [                          // Mapeamento de pastas
    ['/', './frontend']
  ]
} 