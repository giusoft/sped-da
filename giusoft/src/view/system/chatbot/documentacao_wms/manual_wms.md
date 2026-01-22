# Cadastro de Áreas
O cadastro de áreas em um Filial é fundamental para otimizar o espaço e facilitar o controle de estoque.
Para realizar o cadastro de uma área no WMS, acesse o menu Cadastros > Áreas, clique em Adicionar e preencha as seguintes informações: descrição, código e, se necessário, o código de faturamento. Informe as regiões correspondentes, ative a área e confirme o cadastro.
Ative as flags necessárias abaixo para a parametrização da área.
- Recebimento - Área destinada ao recebimento de cargas.
- Sobra - Local para armazenar somente sobras.
- Posicionável - Indica que a área é posicionável.
- Espera - Itens aguardando processamento.
- Divergência - Área destinada a itens com divergências.
- Avaria - Local para armazenar somente avarias.
- Lote Único - Recebimento exclusivo de um lote específico.
- Armazenagem - Área destinada à armazenagem.
- Expedição - Área destinada à expedição de cargas.
- Falta - Área para recebmento de faltas (saldo sistêmico), para retorno fiscal posteriormente.
- De cima para baixo - Posicionamento em ordem decrescente.
- Reservável - Área que pode ser reservada.

# Cadastro de Posições
O cadastro de posições, também conhecido como endereçamento, tem como objetivo otimizar a organização e a gestão do Filial. Nessa etapa, cada local é devidamente identificado, o que facilita a localização dos produtos, a movimentação interna e o controle eficiente do estoque. Para criar uma posição no WMS, acesse o menu Cadastro > Posições, clique em Nova e preencha as seguintes informações: Filial, tipo de posição (blocada, porta-palete, drive-in), área, observações (se necessário), largura, altura e comprimento (em metros), peso suportado e quantidade máxima de UMAs suportadas na posição. Preencha também os campos abaixo:
Módulo - Conjunto de prédios em um determinado espaço
Rua - Corredores que cruzam o espaço, servindo como a principal via de circulação de pessoas e máquinas.
Prédio ou blocado - Prédio refere-se a cada drive-in, porta paletes, blocado diferente dos outros tipos não possuem repartições em níveis.
Andar - refere-se aos níveis de um prédio.
Apartamento - Refere-se à quantidade de espaços em cada andar que podem ser utilizados para armazenagem.
Após o preenchimento, marque as flags "Ativo" e "Picking" (se for uma posição de picking). Caso seja permitido armazenar apenas paletes vazios, selecione também a opção "Apenas paletes vazios".
O cadastro de posições pode ser realizado em lote por meio de um arquivo CSV. Para isso, clique na aba "Cadastro de posições em lote", baixe o arquivo modelo no botão "Baixar modelo CSV", preencha-o com as informações necessárias e salve-o como CSV separado por ponto e vírgula. Após finalizar o arquivo, importe-o para o sistema e confirme. A atualização das posições também pode ser realizada em lote, na aba "Atualização em lote". Selecione as posições que deseja atualizar e confirme. Na tela seguinte, indique as informações que devem ser atualizadas e confirme novamente.

# Cadastro de Unidades
No menu unidades, são cadastradas todas as unidades de medida utilizadas no estoque. Elas são cadastradas automaticamente ao importar um arquivo XML de nota fiscal ou podem ser cadastradas manualmente. Para cadastrar, acesse o menu Cadastro > Unidades, clique em Adicionar, informe a descrição, a sigla e confirme. Os campos peso bruto e líquido, largura, altura, comprimento, palete lastro e altura devem ser preenchidos somente em casos específicos.

# Cadastro de Tipos
No menu Cadastros > Tipos, cadastramos os tipos de produtos que serão utilizados no cadastro de itens na aba Dados. Clique em Adicionar, informe a descrição e confirme.

# Cadastro de Grupos
No menu Cadastros > Grupos, cadastramos a qual grupo os itens pertencem, e esses grupos serão utilizados na aba Dados do cadastro de itens. Clique em Adicionar, informe a descrição e confirme.

# Cadastro de Itens
O cadastro de itens é o processo de catalogar, classificar e organizar os produtos para controlar o estoque, sendo uma parte essencial para o recebimento e a saída do Filial. O cadastro de itens influencia no comportamento das operações realizadas em todo o sistema.

### Aba Dados
Para realizar o cadastro de itens, acesse o menu Cadastros > Itens, clique em Novo, preencha os campos abaixo e confirme.
Nome - Nome do item a ser cadastrado.
Descrição - Descrição do item a ser cadastrado.
Código - Código utilizado para identificar o item no Filial.
Código de barras - Código identificador presente na embalagem do produto.
Cliente - Proprietário do item.
Fornecedor - Caso seja necessário.
Grupo - Grupo ao qual o item pertence, como produto acabado, matéria-prima, entre outros.
Tipo - Tipo do item, como alimento, equipamento, consumível, entre outros.
Prazo validade (dias) - Prazo em que um determinado item está próprio para consumo.
Shelf life (dias) - Período em que o produto pode ser comercializado.
Temperatura ideal (°C) / Temperatura limite (°C) - Para itens que possuem controle de temperatura.
SKU reposição picking - SKU para reposição no processo de picking.
SKU operação - SKU utilizado na operação.
SKU pedido pelo cliente - SKU referente ao pedido do cliente.
Prioridade de saída - Determina a regra de reserva e saída do item: FIFO, FEFO, LIFO.
Picking - Qtd. mínima - Quantidade mínima no processo de picking.
Picking - Qtd. máxima - Quantidade máxima no processo de picking.
Curva - Curva de estoque do item (se aplicável).

### Aba SKUs
Na aba SKUs, serão preenchidas as informações de código de barras, unidade de medida, dimensões e regra de paletização do item.
Nome - Nome do item a ser cadastrado; essa descrição aparecerá no estoque, nos relatórios e em todo o sistema.
Código - Código utilizado para identificar o item no Filial.
Código de barras - Código de barras do item.
Unidade - Unidade de medida do item.
Quantidade - Quantidade fiscal do item.
Peso liquido/bruto - Peso líquido e bruto unitário do item.
Largura/comprimento/altura - Dimensões unitárias do item.
Quantidade palete lastro/altura - Regra de paletização.
Empilhamento máximo - Quantidade máxima de empilhamento.
Valor - Valor unitário, se necessário.

### Aba Ocorrência
Na aba Ocorrências, podem ser cadastradas diversas ocorrências para o item. Para incluir no cadastro, descreva a ocorrência, informe a data, o colaborador, o tipo de ocorrência, marque se a informação é pública ou não e confirme.

### Aba Áreas
Na aba Áreas, devem ser adicionadas as áreas para o recebimento do item. Clique na caixa Adicionar área, será exibida uma lista suspensa com todas as áreas cadastradas para o Filial. Selecione as áreas para o item e confirme.

### Aba Imagens
Na aba Imagens, podem ser adicionadas imagens correspondentes ao item. Informe a descrição da imagem, clique em "Selecionar arquivo", adicione a imagem e clique no botão Incluir. Observe que existem outros botões, como Utilizar webcam e Capturar imagem.

### Aba Conversão de SKUs
Nesta aba, podem ser cadastrados códigos de fornecedores para conversão para o código do cliente no momento da importação do XML. Informe o CNPJ, o código do fornecedor, o SKU (WMS) e confirme. O cadastro dos itens também pode ser realizado através da importação da nota fiscal para recebimento por meio de um arquivo XML, ou também por um arquivo CSV, no menu Cadastros > Itens, aba Importar. Prepare o arquivo conforme o modelo sugerido pelo sistema, salve e importe. O WMS também permite a atualização em lote na aba "Atualização em lote". Clique no botão Atualização em lote, informe os filtros necessários para buscar os itens a serem alterados e confirme. Na próxima tela, serão listados os itens do filtro. Informe quais campos do cadastro serão alterados e confirme.

# Cadastro de Proprietário / Fornecedor / Destinatário
No WMS, o cadastro de proprietário, fornecedor e destinatário é realizado no cadastro de empresas.
Para cadastrar, acesse o menu Cadastros > Empresas, clique em Novo e preencha os campos. Após o preenchimento, serão criadas as abas abaixo:

### Aba Dados pessoais
Nesta aba, serão preenchidas as informações:
Nome - Nome da empresa.
Apelido - Nome de exibição nas telas do sistema.
Senha e confirmação - Caso seja necessário disponibilizar para o cliente realizar consultas.
Razão Social - Nome da empresa cadastrado no site da Receita Federal.
E-mail - Caso necessário.
Site - Caso necessário.
Situação - Ativar ou desativar.
Unidade - A qual unidade pertence a empresa.
CNPJ - Número do Cadastro Nacional da Pessoa Jurídica.
Inscrição municipal/estadual - Número da inscrição do cliente.

Além dos campos, são exibidas as flags abaixo:
Matriz - Flegar caso esteja cadastrando o CNPJ da matriz.
Cliente - Mesmo que proprietário.
Cliente final - Mesmo que destinatário.
Fornecedor - Flegar caso seja um cadastro de fornecedor.
Transportadora - Flegar caso seja um cadastro de transportadora.

### Aba Operação
Nesta aba serão exibidos os flags abaixo para parametrização do sistema.
Tratamento fiscal - Ao realizar esse flag, todas as operações dentro do sistema necessitará de nota fiscal.
Exige UMA entrada convencional - Ao realizar entrada convencional será necessário informar a UMA.
Permitir portaria sem OS - Retirar a obrigatoriade de OS para autorização de entrada e saída na portaria.
Indicar posição na entrada - Obriga ao usuário a informar uma posição ao realizar uma entrada.
Segunda separação - Para realização de separação dupla.
Priorizar palete aberto/fechado - Prioriza paletes abertos ou fechados no momento da reserva.
Exigir SKU na separação - Exige o lançamento do SKU ao realizar separação.

Além dos flags exibem também os campos abaixo:
Código de sistema externo - Caso precise informar o código do sistema do cliente.
Tipo de separação - Informar o tipo de separação, por rua ou por item.
Tipos de entradas - Informar o tipo de entrada para o cliente, dinâmica, convencional ou com UMA virgem.
Quantidade de posições contratadas - Quantidades de posições contratadas pelo cliente.

### Aba Endereços
Nesta aba, cadastre todas as informações do endereço do cliente. Para clientes com tratamento fiscal, esses dados influenciarão na emissão das notas fiscais.

### Aba Filial
Nesta aba, cadastre os filial aos quais o cadastro pertence.

### Aba Ocorrência
Na aba Ocorrências, podem ser cadastradas diversas ocorrências para a empresa.
Para incluir no cadastro, descreva a ocorrência, informe a data, o colaborador, o tipo de ocorrência, marque se a informação é pública ou não e confirme.

### Aba Anexos
Nesta aba, é possível inserir anexos relevantes ao cadastro da empresa.

### Aba Prioridades
Na aba Prioridades de Reserva, são exibidos botões para parametrização das prioridades do sistema. Utilize o botão de alternância para habilitar ou desabilitar a prioridade principal. Em seguida, utilize os botões de seta para cima ou para baixo para definir a ordem de prioridade das demais opções. Para garantir o funcionamento correto do sistema, habilite primeiro a prioridade principal e, em seguida, as demais conforme necessário.
Área de picking - Buscar saldo posicionado no picking.
Prioridade do cadastro do item - Buscar com base na prioridade de saída do cadastro do item: FIFO,FEFO,LIFO.
Menor número do prédio - Buscar os menores saldos do prédio.
Maior número do prédio - Buscar os maiores saldos do prédio.
Palete aberto - Buscar os paletes abertos (parciais).
Palete fechado - Buscar os paletes fechado (ignorar os parciais enquanto houver saldo)
Palete não posicionado - Buscar paletes que não estão posicionados.

### Aba Permissões
Nesta aba, é possível conceder acesso a telas específicas do sistema para o cliente. Ao habilitar essa funcionalidade, você poderá controlar quais áreas do sistema o cliente poderá visualizar e interagir. Essa configuração é essencial para personalizar a experiência do cliente e garantir que ele tenha acesso apenas às informações pertinentes ao seu perfil.

# Ajuste de saldo
O menu Ferramentas > Ajuste de saldo é utilizado para realizar ajustes de saldos de uma determinada UMA,a ferramente funciona da mesma forma que o inventário, dar baixa no saldo anterior e entrada no saldo ajustado. Importante lembrar que para clientes com tratamento fiscal, o saldo retornará bloqueado, sendo necessário associar uma nota fiscal para liberar o saldo.
Após abrir o menu, informar UMA a ser ajustada e confirmar, serão informados os dados da UMA, informações sobre o item e quantidade atual, confirmar, informar o código SKU, lote, data de validade e fabricação e confirmar, na próxima tela serão oferecidas algumas opções de ajuste, realizar o preenchimento e confirmar.

# Edição de UMA
O menu Ferramentas > Edição de UMA, é utilizado quando há necessidade de alteração de alguns dados de uma determinada UMA como lote, data de fabricação e validade. Após clicar no menu, informar a UMA a ser editada, infromar o lote e datas a serem alteradas, os campos proprietário, item, serial e nota fiscal, serão informados somente se a UMA a ser editada, possuir diversos proprietários, SKUs, serial, itens ou notas.

# Bloqueio
A ferramenta de bloqueio é utilizada para realizar bloqueio e desbloqueio de UMAs, os bloqueios podem ser do tipo: bloqueada, avariada, indivisível, qualidade, além do tipo, podem ser informados observações. Para realizar o bloqueio utilizar o menu Ferramentas > Bloqueio, clicar no botão bloqueio/desbloqueio, informar a UMA e confirmar, será exibida uma tela com as informações da UMA, conferir e confirmar, indicar o tipo de bloqueio, observações e confirmar.

# NFe clientes
O menu NFe cliente é utilizado para importação de XML, ou criação de notas fiscais de entrada para o sistema. Clicar no menu Ferramentas > NFe cliente, clicar no botão importar nota fiscal, realizar os flags se necessário, informar o proprietário, selecionar o arquivo e confirmar, o campo fornecedor só deverá ser preenchido para casos de conversão de SKU. Para criar a nota fiscal avulso, clicar em Novo, preencher todos os campos e confirmar, preencher todas as abas e confirmar.

# NFe Internas
O menu Ferramentas > Nfe – Internas é utilizado para realizar a emissão de notas fiscais. As notas fiscais podem ser emitidas a partir de uma OS de saída ou emissão avulsa no caminho mencionado acima. O sistema permite que realizemos a emissão de diversos tipos de notas, as mais comuns são as notas de retorno de armazenagem e retorno simbólico. A nota fiscal de retorno de armazenagem é um documento que registra a devolução de mercadorias a um Filial, emitida pelo Filial geral e destinada ao depositante. Os CFOPs (Código de Operações Fiscais) mais comuns são: 5906-5907 para operações realizadas dentro do estado e 6906-6907 para operações realizadas fora do estado (Interestadual). As notas de retorno emitidas no sistema são um espelho da nota de entrada que foi enviada para recepção da carga que está armazenada, tratando-se de uma duplicata da nota de entrada, todas as condições fiscais da nota de entrada devem ser respeitadas no momento da emissão dessa nota de retorno. Para realizar a emissão da nota fiscal através de uma OS de saída, o cliente deverá estar parametrizado com tratamento fiscal, esse parâmetro irá habilitar um botão que irá direcionar para o menu de emissão, refletindo os dados da OS e as condições fiscais da nota de entrada que foi reservada. Após clicar no botão Criar Nfe, o sistema irá direcionar para a tela de emissão, e iniciaremos o preenchimento dos dados da nota fiscal. CFOP – De acordo com a operação que está sendo realizada, fiscalmente há diversos tipos de operações fiscais, tendo que ser analisado qual a operação se encaixa para cada caso, conforme mencionado anteriormente o mais utilizado dentro do WMS são: 5906-5907 / 6906-6907. Informações como: Filial, tipo, cliente são preenchidas automaticamente de acordo com a OS, transportadora, volume, data de emissão e movimento serão preenchidas pelo emissor da nota. Na aba item, serão trazidas as informações dos itens lançados na OS, CFOP de entrada, código SKU, se o item está ativo e apto, NCM, descrição, unidade de medida, quantidade programada, valor unitário, cálculo do valor total, ICMS, IPI, PIS e COFINS. Na aba UMA: indicará todas as umas programadas na OS de saída, caso seja uma nota fiscal avulsa exibirá somente os itens que foram inclusos, aba somente para verificação. Na aba NF-e: Nesta aba serão preenchidas todas as condições fiscais da nota, esta etapa da emissão exige bastante atenção. Identificador de destino – Podem existir três tipos de operações, selecionar de acordo com a operação que será realizada, verificar com o setor fiscal. 1 – Operação Interna - dentro do próprio estado. 2 – Operação Interestadual - fora do estado. 3 – Operação com Exterior - fora do país. Identificação IE Destinatário – Nesse campo será indicado se haverá incidência ou não do ICMS, 1 – Contribuinte do ICMS 9 – Não contribuinte. Finalidade da Emissão – Qual o tipo de nota será emitida, no sistema atualmente temos: 1 – NF-e normal - A NFe normal serve para registrar a circulação de mercadorias ou a prestação de serviços, seja entre empresas (B2B) ou diretamente ao consumidor final (B2C). 2 – NF-e complementar - A Nota Fiscal Eletrônica complementar é utilizada para adicionar informações que estavam ausentes ou corrigir dados incorretos em uma NFe previamente autorizada pelo Fisco. 3 – NF-e de ajuste - A Nota Fiscal Eletrônica de ajuste é empregada para retificar dados de uma NFe que já foi emitida e aprovada pelo Fisco, quando os erros identificados não podem ser corrigidos por meio de uma NFe complementar. 4 – Devolução/Retorno - A Nota Fiscal Eletrônica de devolução de mercadoria é utilizada para registrar o retorno de produtos que já haviam sido comprados e cuja venda foi registrada em uma NFe anterior. Sua emissão é necessária para oficializar a devolução dos itens ao vendedor, seja por defeitos nos produtos, insatisfação do cliente ou outras razões. Modalidade Frete – Qual o tipo de frete e para quem será feita a cobrança: 0 – Por conta do emitente 1 – Por conta do destinatário/remetente 2 – Por conta de terceiros 9 – Sem frete. Identificador de intermediador – Normalmente as operações ocorrem com o código 0 – Operação sem intermediador, porém fiscalmente pode ocorrer de ter um intermediador, 1 – Operação em site ou plataformas de terceiros. Informações – Nesse campo são preenchidas as informações sobre tributação que são as mesmas da nota de origem, o campo é selecionável e as opções podem ser cadastradas no sistema. Informações fisco – Nesse campo podem ser detalhadas demais informações da operação, o campo pode ser preenchido a mão livre, existe um limite de caracteres. Informações contribuinte– Neste campo o preenchimento é automático caso a nota seja emitida através de uma OS de saída: Notas de entradas, número da OS entre outras informações. Espécie transportadora – Qual o tipo de volume que será transportado, colocar a mesma informação da nota de entrada, exemplos: caixas, volumes, litros, quilos, etc. Quantidade espécie – Quantidade a ser transportada, preencher de acordo com a quantidade carregada, pode divergir da quantidade programada, exemplo: carregamento de 1000 cxs quantidade fiscal, e um volume de 10 pallets. Os outros campos como: Marca, Outras despesas, Valor do desconto, Valor do seguro, Valor do frete, preencher somente se necessário. Campo Valor Total é calculado automaticamente. E-mails: preencher com e-mails para envio da nota fiscal. Chave de referência: Para casos de emissão de nota fiscal complementar, citado em finalidade de emissão. Após preenchimento e validação de todos os itens da nota clicar em confirmar e logo após emitir a nota fiscal. Se a nota fiscal for emitida avulso, deverá ser preenchido todos os campos em todas as abas, assim como as tributações, ICMS, IPI, PIS, COFINS, verificar junto ao setor fiscal a tributação para cada caso.

# Cancelamento de NFe
Para cancelamento da nota fiscal, identificar o botão Opções da NFe, que fica localizado no menu Ferramentas > Notas fiscais internas na aba Nfe, serão exibidos alguns comandos que poderão ser realizados como: Cancelar Nfe, Obter XML, Imprimir danfe, CC-e ( carta de correção), e estornar a nota. Clicar na opção cancelar NFe, descrever o motivo e confirmar.

# Estorno de NFe
Este tipo de nota é emitida automaticamente ao clicar no botão que fica localizado no mesmo local para cancelamento da nota, pode ser utilizada como opção para os casos de perdas do prazo de cancelamento de 24 horas, deverá ser comunicado e acordado com o destinatário, ao final da emissão salvar os arquivos, este tipo de nota não fica listada no menu.

# Emissão de CC-e
A carta de correção pode ser encontrada no mesmo local onde fica o botão para cancelamento da Nfe, ela pode ser utilizada em alguns casos para correção de alguns campos que forem digitados errados no momento da emissão da nota como, quantidade, especie, informações do fisco e etc. A carta de correção não pode ser utilizada para correção de SKU na nota nem CFOP caso mude as condições tributárias da nota.

# Associar UMAs a NFe
O menu Ferramentas > Associar UMAs a NFe, é utilizado para vincular UMAs a notas fiscais importadas no sistema. Após clicar no menu, informar a nota fiscal, e confirmar, será listado todas as UMAS no sistema sem vínculo para os itens que pertecem a nota fiscal informada, selecionar as UMAs para associação e confimar. Além da nota fiscal, podem ser informados também o código SKU que deseja associar e o valor unitário, para casos onde existam UMAs com valores unitários diferentes.

# Arquivo NFe
Menu utilizado para obter os arquivos XML e PDF das notas importadas e emitidas no sistema, dentro do período especificado. Ao informar os filtros de proprietário, tipo entrada ou saída, data inicial e final, se há tratamento fiscal, e flegar as opções XML e PDF, são geradas pastas com todos os arquivos zipados.

# Integração Winthor
No menu módulos podemos encontrar as opções de entrada, saída e inventário onde serão realizadas a comunicação WMSxWinthor. Entrada: clicar no menu entrada, informar o número do bônus “pedido” ou OS e confirmar, na próxima tela exibirá as informações da OS, informar a conferência da carga e confirmar as quantidades, feito isso as informações serão enviadas para o sistema Winthor. Saída: clicar no menu saída, informar o número do pedido ou OS, informar a data e confirmar, na próxima tela exibirá as informações da OS, informar a conferência da carga e confirmar as quantidades, feito isso as informações serão enviadas para o sistema Winthor.Inventário: clicar no menu inventário, informar a numeração do inventário gerado pelo Winthor, informar os SKUs a serem excluídos da contagem, o nome do usuário e confirmar.

# Pré-inventário
O pré-inventário envolve a organização do estoque, a revisão minuciosa dos registros e o treinamento adequado da equipe. As seguintes etapas devem ser realizadas antes do inventário:
Desfazer todas as OSs em andamento: certifique-se de desfazer as ordens de serviço (OS) que não estão executadas. Posicionar corretamente os produtos: garanta que os produtos estejam fisicamente posicionados, e que todas as UMAs estejam posicionadas no sistema, posicionamento incorreto resulta em contagens incorretas. Se a UMA não estiver posicionada no sistema, e não for contada em posição alguma durante o inventário, não terá seu saldo atualizado, resultando numa divergência. Analisar o relatório de pendências: acesse o menu Relatórios > Pendências, este documento indicará todas as OSs iniciadas e não executadas, além das UMAs não posicionadas no sistema. Após a conclusão dessas etapas, estaremos prontos para criar as OSs de inventário.

# Regras do inventário
O inventário visa realizar a contagem e a listagem de todos os produtos e materiais de um Filial. Ele pode ser realizado de diferentes formas:
Inventário geral: contagem de todos os produtos do Filial.
Inventário cíclico: contagem periódica, que pode ocorrer em dias, meses, etc.
Inventário parcial: contagem de apenas uma posição específica, SKU, rua, prédio, etc.
Para realizar um inventário no WMS é necessário selecionar pelo menos uma posição para a contagem. Vale ressaltar que todos as UMAs posicionadas nas posições selecionadas não serão reserváveis durante o inventário.
Para garantir acuracidade, é necessário realizar a quantidade mínima de contagens, que pode ser parametrizada através dos parâmetros CONTAGENS_INVENTARIO para inventário normal, ou CONTAGENS_INVENTARIO_SIMPLIFICADO para inventário simplificado. A quantidade minima de contagens que pode ser definida é dois.
O WMS oferece a opção de utilizar os saldos existentes como a primeira contagem, se assim o responsável pelo inventário desejar. Caso existam posições vazias, o sistema possui um parâmetro para inserir contagem de posição vazia denominado INSERIR_CONTAGEM_VAZIA_INVENTARIO, para tanto, é necessário este parâmetro estar ativo, a posição deve estar vazia antes do inventário, e a flag “Usar saldo como primeira contagem” deve estar ativa.

# Tipos de inventário no WMS
Existem dois tipos de programação de inventário disponíveis no sistema: Inventário: após a conclusão de todas as etapas de contagem e aceite do inventário, o sistema oferece a opção de “Transferir contagens para saldo atual”. Ao transferir as contagens, o sistema realiza baixa do saldo anterior das UMAs ativas e posicionadas nas posições contadas, e acrescenta um novo saldo correspondente às contagens.Inventário Simplificado: após a contagem, o sistema não oferece a opção de alimentar os saldos baseados na contagem, ou seja, é um inventário para fins de relatório. Este relatório é analisado pelo responsável do inventário para a validação dos saldos.

# OS de inventário
Após selecionar o tipo de programação de inventário a ser realizado, devem ser preenchidos os campos proprietário (caso seja inventariado o saldo de um cliente em específico), e data da previsão. Se o cliente não for especificado, então o sistema registrará o saldo contado de qualquer proprietário. Na aba de itens, é necessário preencher os campos correspondentes às posições que deseja considerar para a contagem, tais como: módulo, lado, código de SKU, e intervalos de rua, intervalo de prédio, andar, apartamento. O sistema utilizará as posições configuradas para registrar contagens, logo o que não atende a especificação das posições não será inventariado. Após realização das contagens de todas as posições registradas na OS, deve-se verificar inconsistências, caso haja, recomendamos apagar as contagens inconsistentes e realizar a contagem novamente. Após o aceite, se o inventário for simplificado, a OS será executada e os relatórios poderão ser extraídos para conferência. Se o inventário utilizado for normal, o usuário poderá transferir as contagens registradas para o sistema, que ajustará o saldo das UMAs conforme contagem, no caso de inventário não simplificado, as UMAs com mais de uma referência de nota fiscal perderão essa referência, e a associação da UMA com a NFe deverá ser feita manualmente através da ferramenta Ferramentas > Associar UMAs a NFe. Ao consultar a UMA, será possível visualizar o histórico completo de informações desde a entrada do SKU na UMA assim como sua NFe. Para UMAs com apenas uma nota fiscal, a referência atual de NF será mantida. Caso seja necessário desfazer a OS por algum motivo, isso pode ser feito sem apagar as contagens registradas se a OS estiver executada. Se a OS ainda não estiver executada, o WMS apagará todas as contagens, sendo necessário realizar novas contagens.

# Relatórios de inventários
O WMS disponibiliza diversos relatórios para consulta dos inventários, para tanto acesse Relatórios > Inventários. Abaixo segue descrição de cada relatório. Resumo: este relatório apresenta um resumo de todas as OSs de inventário em andamento, incluindo informações como número da OS, número do cliente, proprietário, situação, data de início, duração, quantidade de posições a serem inventariadas e concluídas, percentual de contagem, quantidade de contagens realizadas e o operador responsável. Ao clicar no link de OS, haverá redirecionamento para o relatório de inventários “Em andamento” daquela OS. Em Andamento: este relatório exibe o progresso do inventário por OS no sistema. Saldo: este relatório apresenta o saldo total das contagens por OS, com base nos parâmetros informados na tela de pesquisa, ou seja, apenas o saldo final contado no inventário. Pós-inventário: este relatório detalha a acuracidade do estoque, apresentando as informações de saldo da contagem, saldo anterior, a diferença entre o saldo anterior e o saldo novo obtido no inventário, e o ajuste realizado. Financeiro pós-inventário: este relatório detalha a diferença financeira após o inventário. Ao selecionar os filtros de pesquisa, serão exibidas tanto a diferença no saldo contábil quanto a diferença financeira dos SKUs inventariados. Contagens por posição: este relatório exibe a quantidade de contagens realizadas por posição, incluindo informações sobre a UMA, SKU, quantidade, lote, fabricação e validade, proprietário, data e hora da contagem, além de identificar o responsável por cada contagem. Contagens: contagens de todas as OS de inventário em andamento. Performance: Ranking de pessoas por posições contadas. Simplificado: relatório de resultado do inventário simplificado. Pós-Simplificado: relatório de pós-inventário do inventário simplificado.

# Inventário no coletor
Após a abertura da OS de inventário, o usuário responsável pelas contagens iniciará a operação de inventário no coletor. O usuário deve acessar Operação > Inventariar, selecionar a OS a ser inventariada e, em seguida, informar a posição a ser contada, UMA, SKU, quantidade, lote, data de fabricação, data de validade. Caso a posição esteja vazia, basta não bipar a UMA, somente selecionar botão Confirmar da tela de UMA. Durante a contagem, o sistema disponibiliza um botão que exibe as contagens realizadas até o momento, útil para esclarecer dúvidas sobre se a posição que já foi inventariada. Tela OSs de inventário => Início de contagem informar a posição => Informar a UMA => Informar o SKU => Informar a quantidade => Contagem finalizada.

# Entrada de Veículos (Portaria)
Neste menu pode-se realizar o controle de acesso de veículos na portaria, nesta tela são exibidos informações como proprietário, placa, motorista, transportadora entre outros dados, a tela exibe alguns botões de autorização de saída e entrada,e esses horários ficam registrados no sistema. Para realizar um cadastro de um veículo na portaria ir ao menu módulos, veículo, clicar em nova e preencher os campos abaixo. Proprietário - empresa que esta enviando ou retirando cargas do Filial. OS - número programação. Placa, placa da carreta 1 e 2 - placas do cavalo e carreta. Tipo - tipo de veículo. Transportadora - transportadora que está transportando a carga. CNH - documento oficial para cadastro na portaria, uma vez lançado realiza o cadastro da pessoa que esta acessando. Nome - nome do motorista. CPF - número do CPF. Data da chegada - data e horário que o motorista se apresenta na portaria. Observações - caso necessitem de algumas observações, campo livre.

# Entrada de Pessoas (Portaria)
Neste menu pode-se realizar o controle de acesso de pessoas na portaria, nesta tela são exibidos informações como nome, setor, horários de entrada e saída e observações. Para realizar o cadastro ir ao menu módulo, pessoas, clicar em "chegada", preencher as iformações abaixo e confirmar. Uma vez cadastrado, em outras oportunidades assim que lançado o CPF demais campos serão preenchidos. CPF - documento apresentado pela pessoa. RG - documento apresentado pela pessoa. Nome - nome da pessoa. Apelido - apelido. Setor - setor que irá se direcionar (cadastro realizado em cadastro de setores). Data de chegada - data e hora que se apresentou na portaria. Observações - observações necessárias.

# Relatórios das Entradas (Portaria)
Neste menu são exibidos os relatórios dos cadastros de veículos e pessoas, nela são disponibilizadas filtros de pesquisa, que ao ser preenchido e realizado a pesquisa lhe retornará todas as informações das atividades realizadas na portaria, podendo também serem extraidos em algumas extensões. Para acessar os relatórios ir ao menu módulos, relatórios, veículos/pessoas, inserir os filtros e confirmar.

# Operação Informações
No menu Operação > Informações,podemos realizar consultas diversas no WMS, ao informar o número de uma, OS, UMA, código SKU ou posição, o sistema exibirá todas as informações baseado na pesquisa, todas as movimentações realizadas, saldo atual, proprietário, data de fabricação e validade, numero de nota fiscal,entre outras informações.

# Operação Etiquetas
No menu Operação > Etiquetas, podemos realizar impressões diversas de UMAs. UMAs - para todas operações dentro do sistema, entrada, saída,
expedição, separação entre outras. Os tipos de etiquetas para operações são: normal, permanente, separação, expedição, picking, produção. SKUs - etiquetas com o código do produto, informar o proprietário, código SKU, quantidade, impressora e confirmar, caso precise vizualizar o documento ativar a flag pré-visualização. Posições - etiquetas de endereçamento, informar o status ativa ou inativas, módulo, rua, prédio, andar, apartamento, lado e impressora e confirmar. Pessoa - identificação da pessoa, informar a pessoa, qual a impressora se deseja visualizar e confirmar. Equipamento - identificação de equipamentos. Pedido - informações do um pedido cliente. Detalhes de SKU - informações de SKU detalhada com data de fabricação e validade, lote etc.

# Operação Autorização de acesso
No menu Operação > Autorização de acesso, realizamos autorizações de entrada e saída na portaria pelo coletor.

# Operação Entrada
No menu Operação > Entrada, realizamos entradas no coletor, nele estarão listadas todas as OSs de entradas abertas pelo Filial. Entrada Convencional - Após selecionar a OS de entrada, realizar a conferência por SKU, ou seja, contar a quantidade total dos itens a serem recebidos e lançá-los, as UMAs serão geradas respeitando a regra de paletização cadastrada para o item. Informar o código SKU a ser conferido e confirmar. Informar a quantidade total do item e demais informações exigidas de acordo com o cadastro do item e confirmar, nesta etapa também informar a impressora para impressão das UMAs. Conferência finalizada. Entrada com UMA virgem - Após selecionar a OS de entrada, imprimir a quantidade de UMAs necessárias para recebimento dos SKUs, para este tipo de entrada deverá ser informado primeiro a UMA, a quantidade para recebimento de cada UMA fica livre, independente da regra de paletização. Informar a UMA e confirmar. Informar o SKU e confirmar, será exibido uma tela com as informações do cadastro do item, confirmar. Informar a quantidade a ser recebida na UMA e confirmar. Será exibida algumas opções como: finalizar a contagem, adicionar UMA e recontar, incluir todas as contagens e finalizar a contagem e confirmar na próxima tela, entrada com UMA virgem será finalizada. Entrada Dinâmica - Este tipo de entrada ocorre semelhante a entrada com UMA virgem, o processo se inicia na própria OS, clicar no botão entrada dinâmica e confirmar, serão geradas UMAs com saldos bloqueados que serão liberados somente após conferência. Seguir todos os passos da mesma forma que realizado ao realizar uma entrada com UMA virgem até finalizar a OS.

# Operação Posicionamento (Posicionar UMA)
No menu Operação > Posicionamento, realizamos o posicionamento das UMAs no Filial, informar a UMA, confirmar, na próxima tela informar a posição e confirmar.

# Operação Separação
No menu Operação > Separação, realizamos separações no coletor, este menu é utilizado pela área operacional, nele estarão listados todas as OSs de saídas abertas no sistema. Selecionar a OS que irá realizar a separação e seguir os passos abaixo. O sistema irá agrupar por rua as UMAs, na próxima tela deverá selecionar a rua que irá iniciar a separação. Após selecionar a rua, se dará início a separação, nesta tela será listado, todos os itens, UMAs, posições e quantidade a ser separada, informar no campo o número da UMA ou posição que irá separar o produto e confirmar. Após confirmação será exibido a tela para conferencia do item que está sendo separado, se a reserva ocorrer do saldo total da UMA será preciso somente confirmar a quantidade, caso contrário, será necessário a impressão de uma UMA de expedição que será informada no campo e confirmado, logo após informar a posição que será destinada a separação e confirmar, após separar todos itens da OS a separação será concluída.

# Operação Conferência
No menu Operação > Conferência, são realizadas no coletor a conferência de todas as OSs que foram separadas, este menu é utilizado pela área operacional. Selecionar a OS que irá realizar a conferência e seguir os passos abaixo. Após selecionar a OS, na próxima tela serão listados as UMAs que serão conferidas, a conferência nesse momento é cega, desta forma, não são exibidas as informações de quantidade, o usuário deverá informar a UMA a ser conferida e confirmar, na próxima tela, clicar no ícone sinalizado em vermelho, informar a quantidade conferida e confirmar, feito isso a conferência será finalizada, se a quantidade estiver divergente do separado o sistema indicará e não será possível prosseguir com a conferência.

# Operação Apanha
No menu Operação > Apanha, são exibidas todas as OSs de apanhas para serem realizadas no coletor pelo operacional. A utilização dessa OS será feita da mesma forma que a separação, indicada neste documento anteriormente.

# Saída
No menu Operação > Saída, são realizadas as saídas no coletor pelo área operacional das OSs anteriormentes separadas e conferidas, nele estarão listados todas as OSs de saídas abertas no sistema. Selecionar a OS que irá realizar a separação e seguir os passos abaixo. Informar as UMAs e confirmar. Na próxima tela serão exibidas informações do item, validar as informações e confirmar. A saída será concluída, clicar em sim, e seguir com as próximas OSs.

# Mapa do Filial
Este menu disponibiliza uma representação visual do layout do Filial em perspectiva de cima, permitindo a identificação clara das ruas e das respectivas posições de armazenagem.

# Painel de atividades
Exibe o adamento das atividades atuais do sistema de forma visual, neste menu são apresentados diversos quadros que são parametrizados no menu gestão de painel com informações diversas daas atividades que estão sendo realizadas.

# Menu Ocupação
No menu ocupação são exibidos gráficos de ocupação do Filial de acordo com os parâmetros indicados na pesquisa.

# Menu Clientes
Neste menu é exibido graficos relacionando entradas, saídas e saldo atual para o cliente, conforme filtros estabelecidos.

# Gestão de painel
Neste menu são parametrizados os quadros que serão apresentados no painel de atividades através de ativação de alguns botões, assim como o tempo de espera para ser apresentado cada quadro. O quadros para parametrização são: Hoje, Últimos 30 dias, Entradas, Saídas, Atividades, Resumo, Portaria.

# Análise PQR
O menu Análise PQR realiza a classificação dos itens conforme a frequência de consumo, sendo: P para itens de alta frequência, Q para frequência média e R para baixa frequência.

# OS (ordem de serviço)
No sistema WMS as programações são chamadas de **OS (ordem de serviço)**.

# OS de Entrada / Programação de Entrada
Para realizar a abertura de uma OS de entrada, seguir os seguintes passos, no menu planejamento, programação, clicar em nova, informar o tipo de programação entrada, caso seja necessário informar o número cliente, proprietário, destinatário caso necessite, data da previsão e as observações se for necessário, após preenchimento confirmar. Após confirmar será gerado um número sequencial de OS, e serão criadas abas nesta OS, na aba dados, selecionar o tipo de entrada a ser realizada, se o proprietário estiver habilitado para somente um tipo de entrada, este campo será preenchido automaticamente, se estiver habilitado mais de uma opção de entrada, informar qual tipo de entrada abaixo será realizada. Entrada convencional - este tipo de entrada, utiliza dados de paletização do cadastro do item para criar as etiquetas (UMAs) necessárias para armazenagem, basicamente é informado o código dos itens a serem recebidos e a quantidade total, o sistema calcula de acordo com a regra de paletização, gera as etiquetas ao confirmar, e o usuário finaliza a OS. Entrada dinâmica - este tipo de entrada, semelhante a entrada convencional, utiliza os dados de paletização do cadastro do item, a diferença é que ao clicar na entrada dinâmica o sistema irá gerar UMAs com saldos bloqueados, que serão liberados somente após conferência, neste caso não precisa ir a tela de entrada, dentro da OS existe o botão para realizar a entrada dinâmica, precisará ir a tela de entrada somente no momento da conferência para desbloqueio do saldo. Entrada com UMA virgem - este tipo de entrada, diferentemente dos outros dois, não está vinculado a regra de paletização, para realizar esta entrada o usuário deverá imprimir as UMAs antecipadamente, e a medida que forem sendo recebido os itens, irá associar a UMA ao item e quantidade que esta sendo recebida, para este caso a quantidade recebida na UMA poderá ser maior ou menor ao calculo da regra de paletização.

# OS de Saída / Programação de Saída
Semelhante a abertura de entrada, ir ao menu planejamento, programação, clicar em nova, informar o tipo de programação saída, caso seja necessário informar o número cliente, proprietário, destinatário caso necessite, data da previsão e as observações se for necessário, após preenchimento confirmar. Na aba itens, informar o item, quantidade a reservar e confirmar, podem ser utilizados alguns campos como parâmetro para reserva como: data de fabricação e validade, lote, UMA ou nota fiscal, se não informado o sistema irá respeitar os parâmetros informados no cadastro do item e da empresa.

### Reservas (prioridade de saída)
No cadastro do item no campo prioridade de saída, podem ser parametrizados as seguintes regras: FEFO, FIFO, LIFO ou ignorar. FEFO (First Expired, First Out) que significa "primeiro a vencer, primeiro a sair”. FIFO (Fisrt In, First Out) que significa “primeiro a entrar, primeiro a sair”. LIFO(Last In, First Out) que significa “último a entrar, primeiro a sair”.  Em cadastro de empresas existem alguns parâmetros para priorizar a reserva, na aba “operações” poderá ser indicado se a reserva deverá priorizar palete aberto (parciais), ou palete fechado, já na aba “prioridades”, o Filial poderá configurar a ordem que o WMS irá respeitar ao realizar uma reserva, os parâmetros desta aba são: área de picking, prioridade do cadastro do item, menor número do prédio, maior número do prédio, palete aberto, palete fechado, palete não posicionado, cada parâmetro desse exibe alguns botões para habilitar ou desabilitar o parâmetro e setas para cima e para baixo para definir a ordem que será respeitada pelo sistema. Exemplo: queremos que o sistema ao reservar procure primeiro pelos paletes não posicionados, depois paletes fechados e depois prioridade do cadastro do item, ficaria na seguinte ordem abaixo. (1º paletes não posicionados (habilitar e deixar como primeiro da lista), 2º paletes fechados (habilitar e deixar como segundo da lista), 3º prioridade do cadastro do item - FEFO/FIFO/LIFO (habilitar e deixar como terceiro da lista))

# OS de Transferência de Proprietário / Programação de transferência de proprietário
A OS de transferência de proprietário é utilizada quando é preciso transferir todo o estoque ou algum produto específico de um CNPJ para outro, um exemplo: suponhamos que temos um estoque de um cliente que recentemente mudou de CNPJ, o cliente tem tratamento fiscal e as notas precisam ser emitidas a partir da mudança para o novo CNPJ, das opções possíveis para resolver o problema é devolver todo saldo em nota e ser emitido uma nova nota com todo estoque, outra solução é cadastrar o novo CNPJ, cadastrar os SKUs e abrir uma OS de transferência. Para criar a OS de transferência de proprietário, selecionar o menu planejamento > programações > nova > tipo transferência de proprietário > Informar o “proprietário” > informar o “destinatário” > confirmar, após confirmar listar os itens e quantidades a serem transferidas, reservar os itens e clicar em transferir.

# OS de Apanha / Programação de Apanha
A OS de apanha, é um tipo de OS em que pode ser concentrado mais de uma OS de saída, e possibilita a separação dessas OSs conjunta. Suponhamos que em um mesmo veículo teremos entregas para diversas lojas e para cada loja existe uma OS de saída, a separação dessas OSs podem ser realizadas conjuntas em uma única OS.

# Relatórios
O sistema disponibiliza alguns relatórios de diversas operações realizadas no sistema, como entradas, saídas, saldos, programações entre outros.

# Relatórios de Saldo
- Sintético - Exibe o saldo em estoque agrupado por item.
- Analítico - Exibe o saldo de produtos movimentados com base na nota fiscal, incluindo a entrada inicial e todas as saídas registradas até que o estoque correspondente seja totalmente consumido.
- Por Nota Fiscal - Exibe o saldo por nota fiscal e por item, apresentando as informações de entrada da nota e o saldo atual disponível no momento da consulta.
- Movimentações - Exibe um compilado das entradas e saídas dentro do período especificado nos filtros.
- Mudanças de Produto - Exibe as últimas mudanças de saldo de um determinado produto por UMA.
- Ajustes - Exibe os ajustes de saldo realizados por período, conforme os filtros definidos.
Vencimentos - Exibe o saldo e quantidade de dias para vencimento, assim também como saldos que estão vencidos em estoque.

# Relatório de Operações
Exibe todas as operações (entradas e saídas) por produto programadas no sistema.

# Relatório de UMAs
- Saldos - Exibe o saldo por UMAs a partir dos filtros especificados.
- Histórico - Redireciona para a ferramenta informações, e exibe todo histórico da UMA informada no filtro.
- Entradas - Exibe as entradas de UMAs realizadas dentro de um período especificado.
- Saídas - Exibe as saídas de UMAs realizadas dentro de um período especificado.
- Paletizações - Exibe as paletizações que ocorreram em uma determinada UMA.

# Relatório de Entradas
Exibe todas a entradas realizadas dentro do período especificado.

# Relatório de Saídas
Exibe todas as saídas dentro do período especificado.

# Relatório de Separações
- Andamento - Exibe as OSs de separação que estão sendo feitas e suas respectivas porcentagens de execução.
- A separar - Exibe o saldo programado para separação e saldo atualmente reservado.
- Concluída - Exibe a quantidade separada de cada item programado na OS.

# Relatório de Picking
- Saldos - Exibe saldo de itens na posição de picking.
- Itens - Dado um item, procura-se seu saldo nas posições de picking.

# Relatório de Programações
- Resumos - Exibe uma relação de OSs no período especificado e seu status atual, datas e duração.
- Cortes - Exibe o histórico de cortes realizados através da ferramenta de cortes.

# Relatório de Posições
- Posições - Exibe a ocupação por posição.
- Posições contratadas - Exibe a relação entre posições contratadas e posições ocupadas.

# Relatório de Atividades
- Atividades - Lista todas as atividades vinculadas às programações.
- Atividades por etapa - Lista as atividades da OS por etapas específicas, sendo estas: Conferência, Posicionamento, Separação, Saída, Aceite de conferência. Cada uma com sua respectiva duração.

# Relatório de Pendências
Lista algumas pendências operacionais, tais como: OSs em execução, UMAs sem posicionar, OS abertas e não iniciadas, UMAs sem Nota Fiscal.

# Relatório de NFe emitidas
Exibe todas as notas fiscais emitidas dentro do período especificado.

# Relatório de NFe importadas
Exibe todas as notas fiscais importadas dentro do período especificado.

# Relatório de Ocorrências
Lista todas as ocorrências registradas no sistema, assim como as divergências das OSs, o menu exibe um botão para atuação, e link para direcionar para a OS, quando a ocorrência for em uma OS.

