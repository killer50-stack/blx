# Arquivo Virtual

Um sistema de armazenamento de arquivos completo desenvolvido com PHP e SQLite.

## Funcionalidades

- Upload de arquivos (vídeos, imagens e PDFs)
- Visualização de vídeos, imagens e PDFs diretamente no navegador
- Limite de 999 GB de armazenamento total por usuário
- Limite de 29 GB por arquivo enviado
- Listagem e organização dos arquivos enviados
- Opção para excluir arquivos
- Criação e gerenciamento de pastas
- Organização de arquivos em pastas e subpastas
- Integração com Git para controle de versão
- Interface de usuário simples e intuitiva com tema marrom

## Tecnologias Utilizadas

### Backend
- **PHP 7.0+**: Linguagem de programação principal para lógica do servidor
- **SQLite 3**: Banco de dados leve para armazenamento de metadados
- **PDO**: Para conexão segura com o banco de dados

### Frontend
- **HTML5**: Estrutura das páginas e formulários
- **CSS3**: Estilização completa com design responsivo
  - Layout flexível com Flexbox e Grid
  - Tema marrom personalizado com variáveis CSS
  - Animações sutis para melhor experiência do usuário
- **JavaScript**: Interatividade e experiência do usuário
  - Manipulação do DOM para interações dinâmicas
  - Upload de arquivos com feedback visual
  - Modais para criação de pastas e movimentação de arquivos
  - Confirmações de exclusão
  - Navegação sem refresh para algumas operações

## Requisitos

- PHP 7.0 ou superior
- SQLite 3
- Servidor web (XAMPP, WAMP, etc.)
- Navegador moderno com suporte a HTML5 e JavaScript

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
├── folder.php
├── git-command.php
├── git-info.php
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
5. Use o botão "Nova Pasta" para criar pastas e organizar seus arquivos.
6. Clique em uma pasta para navegar dentro dela e ver seu conteúdo.
7. Use o botão "Mover" para mover arquivos entre pastas.
8. Clique em "Informações Git" para acessar o painel de controle do Git, onde você pode:
   - Ver o status atual do repositório
   - Adicionar arquivos ao commit
   - Realizar commits
   - Push para repositório remoto
   - Pull de atualizações

## Recursos de Interface

- **Navegação por breadcrumbs**: Facilita a navegação entre pastas e subpastas
- **Layout responsivo**: Funciona em dispositivos móveis e desktops
- **Feedback visual**: Mensagens de sucesso e erro para todas as operações
- **Visualização avançada**: Visualize imagens, vídeos e PDFs diretamente no navegador
- **Indicador de armazenamento**: Barra visual mostrando o espaço usado

## Observações

- Para aumentar o tamanho máximo de upload, você pode precisar modificar as configurações do PHP no arquivo `php.ini` (post_max_size e upload_max_filesize).
- Todos os arquivos são armazenados localmente na pasta `uploads/`.
- Metadados dos arquivos são armazenados no banco de dados SQLite localizado em `database/storage.db`. 