// JS responsável por funcoes para escanear documentos pelo celular
// public/js/notas_fiscais_mobile_functions.js

function isSafariIOS() {
    const ua = navigator.userAgent;
    const iOS = /iPad|iPhone|iPod/.test(ua);
    const safari = /^((?!chrome|android).)*safari/i.test(ua);
    return iOS && safari;
}


// Função para logar eventos no servidor
async function mobileLog(msg) {
    try {
        await fetch("/api/log_mobile_nf.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ msg })
        });
    } catch (e) {
        console.warn("Falha ao logar no servidor:", e);
    }
}

// Função para corrigir a orientação da imagem com base no EXIF
async function corrigirOrientacaoImagem(file) {
    return new Promise((resolve) => {

        const reader = new FileReader();

        reader.onload = function (e) {
            const img = new Image();

            img.onload = function () {

                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");

                const w = img.width;
                const h = img.height;

                // 🟢 SAFARI iOS → NÃO aplicar rotação
                if (isSafariIOS()) {
                    mobileLog("Safari iOS detectado — ignorando EXIF rotation");

                    canvas.width = w;
                    canvas.height = h;
                    ctx.drawImage(img, 0, 0);

                    resolve(canvas);
                    return;
                }

                // 🟡 Chrome / Android → aplicar EXIF
                if (typeof EXIF === "undefined") {
                    mobileLog("EXIF ausente — render simples");

                    canvas.width = w;
                    canvas.height = h;
                    ctx.drawImage(img, 0, 0);

                    resolve(canvas);
                    return;
                }

                EXIF.getData(file, function () {
                    const orientation = EXIF.getTag(this, "Orientation") || 1;
                    mobileLog("EXIF Orientation aplicada: " + orientation);

                    if (orientation > 4) {
                        canvas.width = h;
                        canvas.height = w;
                    } else {
                        canvas.width = w;
                        canvas.height = h;
                    }

                    switch (orientation) {
                        case 3:
                            ctx.translate(canvas.width, canvas.height);
                            ctx.rotate(Math.PI);
                            break;
                        case 6:
                            ctx.translate(canvas.width, 0);
                            ctx.rotate(Math.PI / 2);
                            break;
                        case 8:
                            ctx.translate(0, canvas.height);
                            ctx.rotate(-Math.PI / 2);
                            break;
                        default:
                            break;
                    }

                    ctx.drawImage(img, 0, 0);
                    resolve(canvas);
                });
            };

            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
    });
}


// Função para carregar jsPDF
async function loadJsPdf() {
    mobileLog("Carregando jsPDF...");
    return new Promise((resolve) => {
        const script = document.createElement("script");
        script.src = "/js/libs/jspdf.umd.min.js";
        script.onload = () => {
            mobileLog("jsPDF carregado.");
            resolve();
        };
        document.body.appendChild(script);
    });
}

// Função para converter a imagem corrigida para PDF
async function converterParaPDF(listaImagens) {
    mobileLog("Convertendo múltiplas imagens para PDF...");

    if (!window.jspdf) {
        await loadJsPdf();
    }

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF("p", "mm", "a4");

    const pageWidth = 210;
    const pageHeight = 297;

    for (let i = 0; i < listaImagens.length; i++) {

        if (i > 0) pdf.addPage();

        await new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {

                const imgRatio = img.width / img.height;
                const pageRatio = pageWidth / pageHeight;

                let w, h;

                if (imgRatio > pageRatio) {
                    w = pageWidth - 20;
                    h = w / imgRatio;
                } else {
                    h = pageHeight - 20;
                    w = h * imgRatio;
                }

                const x = (pageWidth - w) / 2;
                const y = (pageHeight - h) / 2;

                pdf.addImage(img, "JPEG", x, y, w, h);
                resolve();
            };
            img.src = listaImagens[i];
        });
    }

    mobileLog("PDF multipágina gerado com sucesso.");

    return pdf.output("datauristring");
}

document.addEventListener("DOMContentLoaded", () => {
    mobileLog("Página carregada");

    const btnFotografar = document.getElementById("btnFotografar");
    const inputCamera = document.getElementById("inputCamera");
    const preview = document.getElementById("preview");
    const btnEnviar = document.getElementById("btnEnviar");

    const numeroNota = window.NOTA_NUMERO || null;

    if (!btnFotografar || !inputCamera || !preview || !btnEnviar) {
        mobileLog("ERRO: IDs não encontrados no DOM.");
        console.error("IDs não encontrados!");
        return;
    }

    let imagensBase64 = [];

    btnFotografar.addEventListener("click", () => {
        mobileLog("Clique em 'Abrir Câmera'");
        inputCamera.click();
    });

    inputCamera.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (!file) {
            mobileLog("Nenhum arquivo retornado da câmera.");
            return;
        }

        mobileLog("Foto capturada: " + file.name + " - " + file.size + " bytes");

        // Corrigir orientação
        corrigirOrientacaoImagem(file).then((canvas) => {
            const base64 = canvas.toDataURL("image/jpeg");
            imagensBase64.push(base64);

            const img = document.createElement("img");
            img.src = base64;
            img.style.width = "100%";
            img.style.marginTop = "10px";
            img.style.borderRadius = "8px";

            document.getElementById("listaPreviews").appendChild(img);

            btnEnviar.style.display = "block";
            document.getElementById("btnNovaPagina").style.display = "block";

            mobileLog("Preview exibido com sucesso.");
        });
    });

    btnEnviar.addEventListener("click", async () => {

        mobileLog("Clique em 'Salvar Nota'");

        if (!imagensBase64.length) {
            mobileLog("ERRO: imagemBase64 = null");
            alert("Tire a foto primeiro!");
            return;
        }

        if (!numeroNota) {
            mobileLog("ERRO: número da nota ausente");
            alert("Número da nota não encontrado!");
            return;
        }

        btnEnviar.innerText = "Enviando...";
        btnEnviar.disabled = true;

        // Converte imagens para PDF Base64
        const pdfBase64 = await converterParaPDF(imagensBase64);

        // ⛔ Safari não suporta JSON gigantes → substituir por FormData
        mobileLog("Montando FormData...");

        const formData = new FormData();
        formData.append("numero", numeroNota);
        formData.append("pdfBase64", pdfBase64);

        mobileLog("Enviando FormData para API...");

        let resRaw;
        try {
            resRaw = await fetch("/api/nfe_upload_foto.php", {
                method: "POST",
                body: formData
            });
        } catch (e) {
            mobileLog("FALHA NO FETCH: " + e);
            alert("Erro ao enviar para o servidor.");
            btnEnviar.innerText = "Salvar Nota";
            btnEnviar.disabled = false;
            return;
        }

        const text = await resRaw.text();
        mobileLog("Resposta bruta API: " + text);

        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            mobileLog("ERRO ao interpretar JSON da API: " + e);
            alert("Erro inesperado no servidor (JSON inválido).");
            btnEnviar.innerText = "Salvar Nota";
            btnEnviar.disabled = false;
            return;
        }

        if (!json.success) {
            alert("Erro: " + json.message);
            btnEnviar.innerText = "Salvar Nota";
            btnEnviar.disabled = false;
            return;
        }

        alert("Anexo salvo com sucesso!");
        window.location.href = "../notas_fiscais.php";
    });

});

document.getElementById("btnNovaPagina").addEventListener("click", () => {
    inputCamera.value = "";
    inputCamera.click();
});
