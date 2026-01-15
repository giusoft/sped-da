import requests
from flask import Flask, request, jsonify
from flask_cors import CORS
from langchain_ollama.llms import OllamaLLM
from langchain_openai import ChatOpenAI
from langchain_core.prompts import ChatPromptTemplate
from vector import get_retriever
import json

from dotenv import load_dotenv
import os
load_dotenv()

app = Flask(__name__)
CORS(app)

ambienteDesenvolvimento = 0
modelo = 1
maxTokens = 460

if modelo == 1:
    ### Atual modelo, atualmente já responde de forma consideravel.
    model = OllamaLLM(model = "llama3.2", temperature = 0, num_predict = maxTokens)
elif modelo == 2:
    ### Ver um modelo mais potente que responda melhor que o llama3.2
    model = OllamaLLM(model = "llama3.1", temperature = 0, num_predict = maxTokens)
elif modelo == 3:
    model = ChatOpenAI(openai_api_key=os.getenv('OPENAI_API_KEY'), model_name="gpt-3.5-turbo", temperature = 0)

prompt_alexa = ChatPromptTemplate.from_messages([
    (
        "system",
        """
            Você é um roteador de perguntas para um chatbot especializado em WMS. Sua tarefa é seguir estas regras, em ordem de prioridade:

            1. **Analise a pergunta do usuário e determine se ela pode ser resolvida por uma das seguintes funções do Alexa.** Se a pergunta for específica e tiver um claro propósito de consulta, retorne um objeto JSON.

            Sua resposta **DEVE SER SOMENTE O JSON**, sem qualquer texto adicional, explicações ou formatação extra (como `Intent:`, `Params:`).

            As funções disponíveis são:
            - **consultarQuantidadeVeiculoIntent**:         Perguntas como "Tem quantos veículos na portaria agora?".
            - **consultarQuantidadeEntradaVeiculoIntent**:  Perguntas como "Quantos veículos de {{PROPRIETARIO}} entraram hoje?".
            - **consultarQuantidadeSaidaVeiculoIntent**:    Perguntas como "Quantos veículos saíram até {{AGORA}}?".
            - **consultarItemMaiorMovimentacaoIntent**:     Perguntas como "Qual o item com maior movimentação no mês {{DATA}}?".
            - **consultarItemMenorMovimentacaoIntent**:     Perguntas como "Qual o item com menor movimentação de {{PROPRIETARIO}} no mês {{DATA}}?".
            - **consultarQuantidadeOsEntradaIntent**:       Perguntas como "Tem quantas OSs de entrada pra {{DATA}}?".
            - **consultarQuantidadeOsSaidaIntent**:         Perguntas como "Quantas OSs de saída de {{PROPRIETARIO}} abertas hoje?".
            - **consultarStatusOsIntent**:                  Perguntas como "Qual o status da OS {{OS}}?".
            - **consultarQuantidadeOsExecutadaIntent**:     Perguntas como "Quantas programações foram executadas ontem?".
            - **consultarQuantidadeOsNoShowIntent**:        Perguntas como "Quantas programações de {{PROPRIETARIO}} não foram executadas no dia {{DATA}}?".
            - **consultarMediaTempoExecucaoOsIntent**:      Perguntas como "Qual a média de tempo de execução das programações de ontem?".
            - **consultarSaldoAtualProdutoIntent**:         Perguntas como "Qual o saldo atual do produto '{{PRODUTO}}'?"
            - **consultarVeiculosClientesPortariaIntent**:  Perguntas como "Quais veículos de quais clientes estão aguardando na portaria?".
            - **consultarClientesAtendidosIntent**:         Perguntas como "Quais clientes estão sendo atendidos agora?".
            - **consultarOsSendoCarregadasIntent**:         Perguntas como "Quais OS estão sendo carregadas agora?".

            Se a pergunta tiver múltiplos parâmetros (ex: PROPRIETARIO e DATA), inclua todos no objeto `params`. Se não tiver parâmetros, o objeto `params` deve ser um objeto vazio `{{}}`.

            Exemplo de saída para uma pergunta específica:
            `{{ "intent": "consultarSaldoAtualProdutoIntent", "params": {{ "PRODUTO": "farinha" }} }}`


            2. **Restrições:**
            - Você é um especialista em sistemas WMS. Responda apenas com base nos dados fornecidos. Não inclua informações externas ou opiniões.
            - Não responda nada relacionado a desconto.
        """
    ),
    ("user", "Pergunta: {question}")
])

prompt_normal = ChatPromptTemplate.from_messages([
    ("system", "Você é um especialista em sistemas WMS. Responda apenas com base nos dados fornecidos. Não inclua informações externas ou opiniões."),
    ("user", "Pergunta: {question}"),
    ("system", "Contexto para análise e resposta: {documentos}. Lembrando que é para responder sobre o que foi perguntado {question}"),
    ("system", "Não fale nada que não seja asociado a documentação e não responda nada relacionado a desconto")
])

@app.route("/chat", methods=["POST"])
def chat():
    retriever = get_retriever()

    data = request.get_json()
    question = data.get("message", "")
    query_mode_active = data.get("queryMode", False)

    if query_mode_active:
        current_prompt = prompt_alexa
        chain = current_prompt | model
        result = chain.invoke({"question": question})
        response_text = result.content if hasattr(result, "content") else str(result)
        try:
            alexa_intent = json.loads(response_text)

            if "intent" in alexa_intent:
                print(f"Intenção do Alexa detectada: {alexa_intent['intent']}")

                params = {
                    "rota": "alexa",
                    "recurso": alexa_intent["intent"],
                    "idPessoasProprietario": "1"
                }

                payload = {}
                if "params" in alexa_intent and alexa_intent["params"]:
                    payload = alexa_intent["params"]
                print(f"Payload a ser enviado: {json.dumps(payload, indent=2, ensure_ascii=False)}")

                base_url = "https://alexa.giusoft.com.br/wms/giusoft/res/api/index.php"
                if ambienteDesenvolvimento:
                    base_url = "http://localhost/wms/logiclog/res/api/index.php"

                # Faz a requisição POST
                try:
                    alexa_response = requests.post(base_url, params=params, json=payload)
                    alexa_response.raise_for_status()

                    try:
                        alexa_data = alexa_response.json()
                        final_response = alexa_data.get("message", "Erro ao obter 'message'.")
                        return jsonify({"response": final_response})
                    except json.JSONDecodeError:
                        print(f"A API da Alexa retornou algo que não é JSON: {alexa_response.text}")
                        return jsonify({"response": "Houve um problema ao processar sua solicitação."})

                except requests.exceptions.RequestException as e:
                    print(f"Erro ao conectar ou processar servidor: {e}")
                    return jsonify({"response": "Não foi possível obter a resposta no momento. Tente novamente."})

        except json.JSONDecodeError:
            print("Resposta do LLM não é um JSON válido.")
        return jsonify({"response": response_text})
    else:
        current_prompt = prompt_normal
        chain = current_prompt | model
        retriever_response = retriever.invoke(question)

        if hasattr(retriever_response, "results"):
            docs = retriever_response.results
        else:
            docs = retriever_response

        documentos = "\n\n".join([doc.page_content for doc in docs])

        result = chain.invoke({"documentos": documentos, "question": question})
        response_text = result.content if hasattr(result, "content") else str(result)

        return jsonify({"response": response_text})

if __name__ == "__main__":
    app.run(debug=True)
