# gFW - Framework PHP da GiuSoft

O gFW nada mais é do que uma camada de abstração para desenvolvimento de aplicativos Web através de objetos e funções desenvolvidas em PHP e Javascript.

Ou seja, a partir de um servidor Web que possibilite a execução de códigos PHP, você pode desenvolver aplicativos robustos, seguros e cheios de recursos com o mínimo esforço!

Veja abaixo um exemplo de código:

```
include_once $gPathDefault . 'gUI.php';

$ui = new gUI('{title: Produtos; table: produtos; permissions: SIUD; ajax: false}');
$ui->run($o, $html);
```

Este código, gera uma página completa com inúmeras funcionalidades como:

- Visualização dos registros em formato de tabela com ordenação e paginação;
- Inserção, exclusão e edição de dados no banco de dados;
- Exportação de dados para PDF, XLS, DOC e CSV;
- Preparação pra impressão;

## Algumas das principais características:

### Programação sem esforço

- Orientado a objetos;
- Responsivo (graças ao Bootstrap);
- Alta performance (uso e inclusão inteligente de arquivos HTML, CSS, JS e cache de BD);
- Minimização do esforço para o desenvolvimento de aplicativos (menos linhas de código);
- Encapsulamento total de código HTML, ou seja, sem preocupação com Front-end;
- Padronização total do site ou aplicativo gerado;
- Permite a reestilização do site ou aplicativo com o uso de temas, sem a necessidade de alteração de nenhum linha de código;
- Dois modos de saída em HTML: enxuta/compacta e depuração (com comentários, exibição de processos, querys, etc.);
- Capacidade de desenvolvimento multilíngue transparente ao programador;
- Não exige nenhuma configuração especial no seu servidor;
- Vários níveis avançados de depuração de código (visualização de resultados de operações com banco de dados, arquivos, etc.);
- Gera automaticamente validações em Javascript;
- Gera automaticamente páginas de consulta, inserção, edição e remoção de dados;
- Gera formulários de entrada de dados com inúmeros tipos de campos (E-mail, CPF, placa, data, hora, etc.) com inserção de código de validação automaticamente;
- Exportação automática para PDF, XLS, DOC e CSV;
- Envio de e-mails;
- Integração com NFE;
- Integração com WebServices;
- Automatização e captura de dados de outros sites através de robôs;

### Banco de dados

- Métodos específicos para acesso a banco de dados;
- Transparência no acesso a bancos de dados através do PDO;
- Compatibilidade com os SGBDs: MySQL, MS SQL, SQLite e outros;

### Relatórios e gráficos

- Geração de relatórios e gráficos de forma simplificada para o programador;
- Gerador de filtros de forma simplificada para o programador;

### Sistema Web descomplicado

- Estrutura básica de autenticação, níveis de acesso, configuração do usuário incluídas (middleware);
- Estrutura do menu dinâmica (em tabela do banco de dados) e sensível ao contexto;
- Montagem automática de menu, de acordo com o nível de acesso do usuário;
- Segurança de acesso a funcionalidades permitidas de acordo com o nível de acesso do usuário;
