# Central do Imóvel — TCC Técnico em Informática

Projeto escolar inspirado **na ideia de navegação** de um portal imobiliário, com interface própria e funcionalidades compatíveis com o documento do TCC. O PDF original **não foi alterado**.

O site é um exemplo simples: **HTML, CSS, JavaScript, PHP e MySQL**, sem frameworks, sem dependências externas e sem integração com sites de anúncios reais.

## 1. O que o site faz

- Página inicial com cartões de imóveis.
- Pesquisa por finalidade (compra ou aluguel), tipo, bairro e preço máximo.
- Página com foto, preço, características e descrição de cada imóvel.
- Formulário para o visitante solicitar atendimento.
- Acesso por e-mail e senha de **administrador** ou **corretor**.
- Painel com cadastro, edição e exclusão de imóveis.
- Administrador pode cadastrar/editar/excluir clientes, corretores e usuários.
- Corretor pode gerenciar imóveis e consultar clientes e atendimentos.
- Administrador pode marcar solicitações de atendimento como respondidas.
- **Painel de personalização da marca:** nome, slogan, cor, logotipo, outra imagem sobre o logotipo e banner.

**Importante:** os anúncios iniciais são FICTÍCIOS. Não copiamos imagens nem a identidade visual do portal de referência. O formulário de atendimento registra os pedidos **no banco de dados**, mas não envia e-mail nem WhatsApp.

## 2. Como instalar no Windows usando XAMPP

1. Baixe e instale o [XAMPP](https://www.apachefriends.org/pt_br/index.html), com Apache, PHP e MySQL/MariaDB.
2. Extraia a pasta **central-do-imovel** dentro de `C:\xampp\htdocs\`.
3. Abra o **XAMPP Control Panel** e clique em `Start` para **Apache** e **MySQL**.
4. No navegador, abra `http://localhost/phpmyadmin/`.
5. Clique em **Importar** e selecione o arquivo `C:\xampp\htdocs\central-do-imovel\banco.sql`.
6. Clique em **Executar**. Isso cria o banco `central_imovel` e seis imóveis fictícios.
7. Acesse `http://localhost/central-do-imovel/instalar.php` e crie **seu primeiro administrador** (nome, e-mail e senha com 8 ou mais caracteres).
8. Acesse `http://localhost/central-do-imovel/login.php`, entre e abra o **Painel**.
9. Depois de criar o administrador, **exclua ou renomeie `instalar.php`** para evitar que o instalador fique disponível.
10. Abra `http://localhost/central-do-imovel/` para ver a página inicial.

**Problemas comuns:**

- Se aparecer “Erro na conexão”, confira se o MySQL está iniciado e se importou `banco.sql`.
- Se o MySQL no XAMPP usa outro usuário/senha, altere `config/conexao.php` (`DB_USUARIO` e `DB_SENHA`).
- Se você renomear a pasta `central-do-imovel`, atualize a constante `BASE_URL` em `config/conexao.php`.
- Use uma versão atual do XAMPP com PHP 8.1 ou superior. Este projeto utiliza funções de PHP 8.
- Se uploads não funcionarem, confira se `uploads/` e `uploads/marca/` permitem gravação para o Apache.

## 3. Onde trocar a marca? (sem programar)

1. Entre no painel com uma conta de **administrador**.
2. Clique na aba **Personalizar marca**.
3. Preencha o novo nome e a frase de apresentação.
4. Em **Logotipo principal**, envie a imagem que ficará no lugar do ícone de casinha.
5. Em **Imagem por cima do logotipo**, envie outra figura (idealmente PNG com fundo transparente). Ela aparece no **canto inferior direito** do logotipo, sobre ele.
6. Em **Banner**, envie uma foto para o fundo da primeira seção do site.
7. Troque a **cor principal** (botões e detalhes) e clique em **Salvar personalização**.
8. Abra o site: nome, logotipo e imagem sobreposta aparecem no topo e no rodapé; o nome aparece também no título da aba do navegador.

**Para voltar ao padrão:** marque “Voltar ao ícone padrão”, “Remover imagem sobreposta” e/ou “Remover banner” e salve.

**Arquivos da marca:**

| O que mudar | Arquivo/Local |
| --- | --- |
| Nome, frase, região, cor e configurações | `config/marca.json` (preferível mudar pelo painel) |
| Imagens enviadas de logotipo, sobreposição e banner | `uploads/marca/` |
| Aparência do logotipo sobreposto (posição/tamanho) | `assets/css/estilo.css`, classe `.logo-sobreposicao` |
| Logo padrão (ícone de casinha) | `config/funcoes.php`, função `logo_marca()` |
| Ícone padrão da aba do navegador | `assets/img/favicon.svg` |
| Cabeçalho, rodapé e nome no navegador | `config/funcoes.php`, funções `cabecalho()` e `rodape()` |
| Imagens de exemplo dos imóveis | `assets/img/casa.svg`, `apartamento.svg`, `terreno.svg`, `comercial.svg` |
| Fotos reais dos anúncios cadastrados | `uploads/` (fazer upload em Painel → Imóveis) |

> Para substituir **a imagem inteira** do logo, envie um novo **Logotipo principal**. Para **colocar uma imagem em cima da que já está**, use **Imagem por cima do logotipo**. Se quiser mudar tamanho ou canto do símbolo, edite `.logo-sobreposicao` no CSS (mude `width`, `height`, `right` e `bottom`).

## 4. Para entender a estrutura (fácil para alunos)

```text
central-do-imovel/
├── index.php                 # lista e pesquisa dos imóveis
├── imovel.php                # detalhes + formulário de interesse
├── contato.php               # salva pedido de atendimento
├── login.php                 # página de login
├── logout.php                # sai da conta
├── instalar.php              # cria o primeiro administrador
├── banco.sql                 # banco MySQL e imóveis ilustrativos
├── config/
│   ├── conexao.php           # nome e acesso ao banco, URL do projeto
│   ├── funcoes.php           # funções comuns, cabeçalho/rodapé
│   └── marca.json            # informações editáveis da marca
├── assets/
│   ├── css/estilo.css        # aparência das páginas
│   ├── js/app.js             # menu em celular
│   └── img/                  # desenhos ilustrativos
├── uploads/                  # fotos cadastradas
│   └── marca/                # suas imagens da marca
└── painel/
    ├── index.php             # resumo
    ├── imoveis.php           # lista/remoção de imóveis
    ├── imovel-form.php       # cadastro/edição com foto
    ├── registros.php         # clientes e corretores
    ├── atendimentos.php      # pedidos dos visitantes
    ├── usuarios.php          # contas e perfis
    └── marca.php             # editor visual da marca
```

## 5. O que cada perfil pode fazer

| Ação | Visitante / cliente | Corretor | Administrador |
| --- | :---: | :---: | :---: |
| Pesquisar imóveis | ✅ | ✅ | ✅ |
| Visualizar detalhes e solicitar contato | ✅ | ✅ | ✅ |
| Cadastrar/editar/excluir imóvel | — | ✅ | ✅ |
| Consultar clientes | — | ✅ | ✅ |
| Consultar atendimentos | — | ✅ | ✅ |
| Gerenciar clientes e corretores | — | — | ✅ |
| Gerenciar contas dos usuários | — | — | ✅ |
| Personalizar a marca | — | — | ✅ |

## 6. Demonstração para apresentar à banca

A sequência abaixo permite mostrar os requisitos principais do TCC:

1. Abra a página inicial e pesquise por `Casa` em `Guaraituba`.
2. Abra um imóvel, veja os detalhes e envie uma solicitação com um e-mail de teste.
3. Entre como administrador e abra **Atendimentos** para visualizar o pedido. Marque-o como `Respondido`.
4. Cadastre um corretor e um novo imóvel usando a foto de sua escolha.
5. Volte à página inicial para mostrar que o novo imóvel aparece na lista.
6. Edite o preço e mostre que ele se atualiza. Exclua um imóvel **sem atendimentos associados** para demonstrar exclusão.
7. Acesse **Personalizar marca**, altere o nome/logotipo e mostre a mudança no topo e no rodapé.
8. Mostre a tabela `imoveis` no phpMyAdmin para explicar como os dados ficam armazenados.

## 7. Limites da primeira versão

Para um trabalho escolar simples, foram deixados de fora: mapa interativo, geolocalização, login com redes sociais, favoritos sincronizados, pagamentos online, inteligência artificial, anúncios de veículos, chat, notificações e integração com portais reais.

O código inclui validação básica, senhas com hash, comandos SQL preparados e verificação de sessão. **É um projeto didático**, não um sistema revisado para uso comercial ou publicação pública. Antes de colocar dados de pessoas reais na internet, seriam necessários testes adicionais, medidas de segurança e atenção à LGPD.

**O arquivo do TCC já escrito não foi editado ou reescrito.** O sistema apresentado é uma primeira implementação para vocês aprenderem e evoluírem.
