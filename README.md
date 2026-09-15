# Sistema de Registro de Denúncias Anônimas de Assédio Moral e Sexual

MVP em PHP nativo (sem framework) e MySQL/MariaDB desenvolvido para o desafio da disciplina de Programação Web II — eixo ESG Governança. A aplicação consiste em um canal seguro para envio anônimo de denúncias, acompanhamento por protocolo e gestão de casos pela equipe de Compliance.

## Tecnologias Utilizadas

* PHP (Nativo)
* MySQL / MariaDB
* Apache Web Server
* HTML5 / CSS3 / JavaScript

## Dependências e Versões Necessárias

* **PHP** - Versão: >= 7.4
* **MySQL / MariaDB** - Versão: >= 5.7
* **Servidor Local**: XAMPP, WAMP ou Laragon

## Como rodar o projeto 

1. Copie a pasta `denuncias-dashboard` para o diretório de arquivos públicos do seu servidor local:
   * **XAMPP**: `htdocs/`
   * **WAMP / Laragon**: `www/`

2. Inicie os serviços do **Apache** e do **MySQL** pelo painel do seu ambiente local.

3. Abra o phpMyAdmin (`http://localhost/phpmyadmin`) e importe a estrutura do banco através do arquivo:
   `database/schema.sql`

4. Verifique as credenciais de acesso ao banco no arquivo `includes/db.php` (configuração padrão: usuário `root` e sem senha)[cite: 1].

5. Abra o navegador e acesse a URL abaixo **uma única vez** para criar o usuário administrador e carregar registros de demonstração:
   `http://localhost/denuncias-dashboard/database/seed.php`[cite: 1]

6. Acesse a aplicação na página principal:
   `http://localhost/denuncias-dashboard/`[cite: 1]

**Como confirmar que está rodando corretamente:**
Ao acessar a URL principal (`http://localhost/denuncias-dashboard/`), o canal público de denúncias será exibido na tela[cite: 1]. Para testar a área restrita, acesse `http://localhost/denuncias-dashboard/admin/login.php` e utilize as credenciais geradas pelo seed:
* **E-mail:** `compliance@empresa.com`
* **Senha:** `compliance123`

## Como rodar os testes

O projeto utiliza testes funcionais manuais cobrindo o fluxo de ponta a ponta. Para testar a aplicação:
1. Envie uma denúncia fictícia pela interface pública e anote o número do protocolo gerado.
2. Acesse a página de consulta com o protocolo para validar o status.
3. Faça login no painel de Compliance (`admin/login.php`), altere o status da denúncia e confirme a atualização do histórico.

## Estrutura do Projeto

* `index.php`, `denunciar.php`, `consultar.php` — Páginas públicas (acesso sem login)[cite: 1].
* `admin/` — Área restrita para a equipe de Compliance (login, painel geral e detalhe de cada caso)[cite: 1].
* `includes/` — Scripts de conexão ao banco, autenticação e regras de negócio[cite: 1].
* `database/schema.sql` — Script SQL de criação das tabelas[cite: 1].
* `database/seed.php` — Script de população inicial do banco com dados de teste[cite: 1].
* `uploads/evidencias/` — Diretório para armazenamento dos arquivos anexados[cite: 1].

## Problemas enfrentados

### Problema 1: Erro de conexão com a base de dados
* **Descrição:** A aplicação retornava mensagem de falha ao tentar conectar ao MySQL.
* **Como solucionar:** Atualizar as configurações de usuário/senha dentro do arquivo `includes/db.php` para bater com as credenciais do ambiente local (ex: XAMPP por padrão usa `root` sem senha)[cite: 1].

### Problema 2: Login no painel administrativo falhando
* **Descrição:** O login em `admin/login.php` informava credenciais inválidas logo após a instalação.
* **Como solucionar:** Executar o script `database/seed.php` via navegador uma vez para popular o banco de dados com a conta inicial de testes[cite: 1].

## Próximos passos

* Adicionar envio automático de e-mail ao alterar o status do chamado.
* Implementar suporte para múltiplos perfis de acesso na área administrativa.
* Criar rotina de criptografia para os arquivos salvos em `uploads/evidencias/`[cite: 1].