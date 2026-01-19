<?php
// espécies de cobranças
namespace Cnab;

class Especie
{
    // Itaú
    const ITAU_DUPLICATA_MERCANTIL = '01';
    const ITAU_NOTA_PROMISSORIA = '02';
    const ITAU_NOTA_DE_SEGURO = '03';
    const ITAU_MENSALIDADE_ESCOLAR = '04';
    const ITAU_RECIBO = '05';
    const ITAU_CONTRATO = '06';
    const ITAU_COSSEGUROS = '07';
    const ITAU_DUPLICATA_DE_SERVICO = '08';
    const ITAU_LETRA_DE_CAMBIO = '09';
    const ITAU_NOTA_DE_DEBITOS = '13';
    const ITAU_DOCUMENTO_DE_DIVIDA = '15';
    const ITAU_ENCARGOS_CONDOMINIAIS = '16';
    const ITAU_CONTRATO_DE_PRESTACAO_DE_SERVICOS = '17';
    const ITAU_DIVERSOS = '99';

    // Caixa
    const CEF_DUPLICATA_MERCANTIL = 2;
    const CEF_NOTA_PROMISSORIA = 12;
    const CEF_DUPLICATA_DE_PRESTACAO_DE_SERVICOS = 4;
    const CEF_NOTA_DE_SEGURO = 16;
    const CEF_LETRA_DE_CAMBIO = 7;
    const CEF_OUTROS = 99;

    //BRADESCO
    const BRADESCO_DUPLICATA = '01';
    const BRADESCO_NOTA_PROMISSORIA = '02';
    const BRADESCO_NOTA_SEGURO = '03';
    const BRADESCO_COBRANCA_SERIADA = '04';
    const BRADESCO_RECIBO = '05';
    const BRADESCO_LETRAS_CAMBIO = '10';
    const BRADESCO_NOTA_DEBITO = '11';
    const BRADESCO_DUPLICATA_SERVICO = '12';
    const BRADESCO_CARTAO_CREDITO = '31';
    const BRADESCO_BOLETO_PROPOSTA = '32';
    const BRADESCO_OUTROS = '99';



    //SICREDI
    const SICREDI_DUPLICATA_MERCANTIL = 'A';
    const SICREDI_DUPLICATA_RURAL = 'B';
    const SICREDI_NOTA_PROMISSORIA = 'C';
    const SICREDI_NOTA_PROMISSORIA_RURAL = 'D';
    const SICREDI_NOTA_SEGURO = 'E';
    const SICREDI_RECIBO = 'G';
    const SICREDI_LETRA_CAMBIO = 'H';
    const SICREDI_NOTA_DEBITO = 'I';
    const SICREDI_DUPLICATA_SERVICO_INDICACAO = 'J';
    const SICREDI_BOLETO_PROPOSTA = 'O';
    const SICREDI_OUTROS = 'K'; 


    // Banco do Brasil
    // Para carteira 11 e 17 modalidade Simples, pode ser usado: 01 – Cheque, 02 – Duplicata Mercantil, 04 –
    // Duplicata de Serviço, 06 – Duplicata Rural, 07 – Letra de Câmbio, 12 – Nota Promissória, 17 - Recibo, 19 –
    // Nota de Debito, 26 – Warrant, 27 – Dívida Ativa de Estado, 28 – Divida Ativa de Município e 29 – Dívida Ativa
    // União. Para carteira 12 (moeda variável) pode ser usado: 02 – Duplicata Mercantil, 04 – Duplicata de Serviço,
    // 07 – Letra de Câmbio, 12 – Nota Promissória, 17 – Recibo e 19 – Nota de Débito. Para carteira 15 (prêmio de
    // seguro) pode ser usado: 16 – Nota de Seguro e 20 – Apólice de Seguro. Para carteira 11/17 modalidade
    // Vinculada e carteira 31, pode ser usado: 02 – Duplicata Mercantil e 04 – Duplicata de Serviço. Para carteira
    // 11/17 modalidade Descontada e carteira 51, pode ser usado: 02 – Duplicata Mercantil, 04 – Duplicata de
    // Serviço, e 07 – Letra de Câmbio. Obs.: O Banco do Brasil encaminha para protesto os seguintes títulos:
    // Duplicata Mercantil, Rural e de Serviço, Letra de Câmbio, e Certidão de Dívida Ativa da União, dos Estados e
    // do Município.
    const BB_CHEQUE = 1;
    const BB_DUPLICATA_MERCANTIL = 2;
    const BB_DUPLICATA_DE_SERVICO = 4;

    const CNAB240_OUTROS = '99';
}
