# Sistema de Armazenamento de Arquivos

Este é um sistema de armazenamento de arquivos completo desenvolvido com PHP, SQLite e JavaScript puro, projetado para funcionar com o XAMPP.

## Funcionalidades

- Upload de arquivos (vídeos, imagens e PDFs)
- Visualização de arquivos diretamente no navegador
- Interface de usuário moderna e responsiva com tema marrom
- Listagem e organização de arquivos enviados
- Exclusão de arquivos
- Controle de armazenamento (limite de 999 GB por usuário)
- Limite de 29 GB por arquivo enviado

## Requisitos

- XAMPP (PHP 7.4 ou superior)
- SQLite3 habilitado no PHP
- Navegador moderno (Chrome, Firefox, Edge, Safari)

## Instalação

1. Clone este repositório ou copie os arquivos para seu diretório htdocs do XAMPP:
   ```
   git clone <url-do-repositorio> /caminho/para/xampp/htdocs/storage-system
   ```

2. Certifique-se de que as extensões PDO e SQLite3 estão habilitadas no seu php.ini.

3. Inicie o servidor Apache no painel de controle do XAMPP.

4. Acesse o sistema em seu navegador:
   ```
   http://localhost/storage-system
   ```

## Estrutura do Projeto

```
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/
│   ├── config.php
│   └── database.php
├── db/
│   └── storage.db (criado automaticamente)
├── uploads/
│   └── files/ (arquivos enviados são armazenados aqui)
├── views/
│   ├── dashboard.php
│   └── view.php
├── delete.php
├── index.php
├── upload.php
└── README.md
```

## Limitações e Configurações

- O PHP possui limites de upload que podem precisar ser ajustados no php.ini:
  - `upload_max_filesize` e `post_max_size` devem ser configurados para permitir uploads de até 29 GB, se necessário.
  - `max_execution_time` deve ser aumentado para permitir o processamento de arquivos grandes.

- Para permitir uploads maiores que 2MB, modifique as seguintes configurações no php.ini:
  ```
  upload_max_filesize = 29G
  post_max_size = 29G
  max_execution_time = 300
  memory_limit = 1024M
  ```

## Segurança

- O sistema implementa verificações de tipo de arquivo para permitir somente imagens, vídeos e PDFs.
- Os nomes de arquivos são sanitizados e renomeados antes do armazenamento para evitar conflitos.
- As consultas SQL utilizam instruções preparadas para prevenir injeção SQL.

## Possíveis Melhorias Futuras

- Autenticação de usuários com sistema de login
- Compartilhamento de arquivos entre usuários
- Painel de administração
- Mais opções de visualização e organização de arquivos
- Compressão de arquivos grandes
- Múltiplos temas visuais 