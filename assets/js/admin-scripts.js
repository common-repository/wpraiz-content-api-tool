function copyToClipboard() {
    var copyText = document.getElementById("api-endpoint");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // Para dispositivos móveis
    document.execCommand("copy");
    alert("Endpoint copiado: " + copyText.value);
}
