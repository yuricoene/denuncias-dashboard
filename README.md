# 🛡️ Sistema de Registro de Denúncias Anônimas (Assédio Moral e Sexual)

> **MVP em PHP Nativo e MySQL/MariaDB** desenvolvido como solução de Governança corporativa (eixo **ESG**) para a disciplina de Programação Web II. 

A aplicação oferece um canal seguro, totalmente anônimo e criptografado para o envio de denúncias, acompanhamento por código de protocolo e gestão simplificada de casos pela equipe de Compliance.

---

## 🛠️ Tecnologias Utilizadas

* **Linguagem:** PHP (Nativo)
* **Banco de Dados:** MySQL / MariaDB
* **Servidor Web:** Apache Web Server
* **Front-end:** HTML5, CSS3 e JavaScript (Nativo)

---

## 📋 Dependências e Requisitos

| Componente | Versão Mínima |
| :--- | :--- |
| **PHP** | `>= 7.4` |
| **MySQL / MariaDB** | `>= 5.7` |
| **Ambiente Local** | XAMPP, WAMP ou Laragon |

---

## 🚀 Como Rodar o Projeto

1. **Copie os arquivos:**
   Mova a pasta `denuncias-dashboard` para o diretório de arquivos públicos do seu servidor local:
   * **XAMPP:** `htdocs/`
   * **WAMP / Laragon:** `www/`

2. **Inicie os Serviços:**
   Abra o painel do seu ambiente local (ex: XAMPP Control Panel) e inicie os módulos **Apache** e **MySQL**.

3. **Importe o Banco de Dados:**
   Acesse o phpMyAdmin (`http://localhost/phpmyadmin`), crie um banco de dados e importe o arquivo de estrutura localizado em:
   `database/schema.sql`

4. **Confira as Credenciais:**
   Verifique as configurações de conexão em `includes/db.php` *(Padrão XAMPP: usuário `root` e sem senha)*.

5. **Popule o Banco de Dados (Seed):**
   Acesse a URL abaixo **uma única vez** no navegador para cadastrar o usuário administrador inicial e criar dados de teste:
   `http://localhost/denuncias-dashboard/database/seed.php`

6. **Acesse a Aplicação:**
   Navegue até o canal público:
   `http://localhost/denuncias-dashboard/`

---

## 🔑 Acesso Administrativo (Painel de Compliance)

Para testar a gestão de casos e alterar status de chamados:

* **URL de Login:** `http://localhost/denuncias-dashboard/admin/login.php`
* **E-mail:** `compliance@empresa.com`
* **Senha:** `compliance123`

---

## 🧪 Como Rodar os Testes

O projeto utiliza **testes funcionais manuais** cobrindo o fluxo de ponta a ponta:

1. **Envio:** Cadastre uma denúncia fictícia no canal público (`index.php`) e anote o protocolo de 8 dígitos gerado.
2. **Consulta:** Acesse a tela de consulta (`consultar.php`), informe o protocolo e valide se as informações conferem.
3. **Gestão:** Faça login no painel restrito (`admin/login.php`), altere o status ou parecer do caso e confirme a atualização no histórico do protocolo.

---

## 📁 Estrutura de Pastas e Arquivos

* `index.php` / `denunciar.php` / `consultar.php` — Interface pública do sistema (acesso livre).
* `admin/` — Área restrita para a equipe de Compliance (autenticação, dashboard e detalhamento).
* `includes/` — Regras de negócio, conexão com o banco de dados e funções globais.
* `database/schema.sql` — Script DDL de criação das tabelas no MySQL.
* `database/seed.php` — População inicial de dados e criação da conta admin.
* `uploads/evidencias/` — Diretório seguro para armazenamento de anexos e evidências enviadas.

---

## ⚠️ Problemas Frequentes & Soluções

* **Erro de Conexão com o Banco de Dados**
  * *causa:* Credenciais incorretas no script PHP.
  * *solução:* Ajuste os parâmetros de usuário e senha no arquivo `includes/db.php` de acordo com a configuração do seu servidor local.

* **Login Falhando na Área Administrativa**
  * *causa:* Tabela de usuários vazia no banco.
  * *solução:* Execute a URL `http://localhost/denuncias-dashboard/database/seed.php` no navegador para criar a conta de testes.

---

## 📌 Próximos Passos (Roadmap)

- [ ] Disparo automático de e-mails para notificações de atualização de status.
- [ ] Implementação de múltiplos perfis de acesso na área de Compliance (Analista vs. Gestor).
- [ ] Rotina de criptografia para os arquivos salvos em `uploads/evidencias/`.
