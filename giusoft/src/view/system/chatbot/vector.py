from langchain_community.document_loaders import TextLoader
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_ollama import OllamaEmbeddings
from langchain_community.vectorstores import FAISS
import os
import re
import shutil

md_file = "./documentacao_wms/manual_wms.md"
faiss_path = "./faiss_index"
mtime_file = "./last_mtime.txt"

embeddings = OllamaEmbeddings(model="nomic-embed-text")


def clean_markdown(text: str) -> str:
    """Limpa apenas o essencial do Markdown, mantendo estrutura semântica."""
    text = re.sub(r"\*{1,2}(.+?)\*{1,2}", r"\1", text)
    text = re.sub(r"\[(.*?)\]\(.*?\)", r"\1", text)
    text = re.sub(r"!\[.*?\]\(.*?\)", "", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()



def get_retriever():
    if os.path.exists(mtime_file):
        with open(mtime_file, "r") as f:
            mtime_antigo = float(f.read().strip())
    else:
        mtime_antigo = None

    mtime_atual = os.path.getmtime(md_file)
    arquivo_mudou = mtime_antigo is None or mtime_atual != mtime_antigo

    if arquivo_mudou or not os.path.exists(faiss_path):
        if os.path.exists(faiss_path):
            shutil.rmtree(faiss_path)

        loader = TextLoader(md_file, encoding="utf-8")
        docs = loader.load()

        all_documents = []
        for doc in docs:
            plain_text = clean_markdown(doc.page_content)
            doc.page_content = plain_text
            doc.metadata["source"] = os.path.basename(md_file)
            all_documents.append(doc)

        text_splitter = RecursiveCharacterTextSplitter(
            separators = ["\n\n", "\n", ".", "#"],
            chunk_size = 700
        )

        split_docs = []
        split_metadatas = []
        for doc in all_documents:
            chunks = text_splitter.split_text(doc.page_content)
            for chunk in chunks:
                split_docs.append(chunk)
                split_metadatas.append(doc.metadata)

        vector_store = FAISS.from_texts(
            texts = split_docs,
            embedding = embeddings,
            metadatas = split_metadatas
        )
        vector_store.save_local(faiss_path)

        with open(mtime_file, "w") as f:
            f.write(str(mtime_atual))
    else:
        vector_store = FAISS.load_local(
            faiss_path,
            embeddings,
            allow_dangerous_deserialization=True
        )

    return vector_store.as_retriever(search_kwargs = {"k": 2})