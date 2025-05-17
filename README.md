# Arquivo Virtual

Um sistema de armazenamento de arquivos completo desenvolvido com PHP e SQLite.

## Funcionalidades

- Upload de arquivos (vídeos, imagens e PDFs)
- Visualização de vídeos, imagens e PDFs diretamente no navegador
- Limite de 999 GB de armazenamento total por usuário
- Limite de 29 GB por arquivo enviado
- Listagem e organização dos arquivos enviados
- Opção para excluir arquivos
- Interface de usuário simples e intuitiva com tema marrom

## Requisitos

- PHP 7.0 ou superior
- SQLite 3
- Servidor web (XAMPP, WAMP, etc.)

## Instalação

1. Clone ou baixe este repositório para o diretório web do seu servidor (htdocs para XAMPP).
2. Inicie seu servidor web (Apache) e certifique-se de que o PHP está habilitado.
3. Navegue até o diretório no seu navegador (ex: http://localhost/arquivo-virtual).
4. O sistema criará automaticamente o banco de dados SQLite e as pastas necessárias na primeira execução.

## Estrutura do Projeto

```
arquivo-virtual/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── config/
│   └── db.php
├── database/
│   └── storage.db (gerado automaticamente)
├── includes/
│   └── functions.php
├── uploads/ (gerado automaticamente)
├── delete.php
├── index.php
├── upload.php
├── view.php
└── README.md
```

## Configuração

O sistema é configurado automaticamente na primeira execução. 
Por padrão, ele define os seguintes limites:

- Limite por arquivo: 29 GB
- Limite total de armazenamento: 999 GB

## Uso

1. Na página inicial, clique no botão "Escolher arquivo" para selecionar um arquivo para upload.
2. Clique em "Enviar Arquivo" para fazer o upload.
3. Na seção "Seus Arquivos", você pode visualizar, baixar ou excluir os arquivos.
4. A barra de armazenamento no topo mostra quanto espaço você já usou.

## Observações

- Para aumentar o tamanho máximo de upload, você pode precisar modificar as configurações do PHP no arquivo `php.ini` (post_max_size e upload_max_filesize).
- Todos os arquivos são armazenados localmente na pasta `uploads/`.
- Metadados dos arquivos são armazenados no banco de dados SQLite localizado em `database/storage.db`. 