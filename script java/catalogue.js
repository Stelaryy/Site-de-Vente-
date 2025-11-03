document.querySelectorAll('.supprimer').forEach(bouton => {
    bouton.addEventListener('click', function(e) {
        e.preventDefault();
        const produit = this.closest('.produit');
        if (!produit) return;

        const id = produit.dataset.id;

        fetch('catalogue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_article=' + encodeURIComponent(id) + '&supprimer=1'
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('message').innerHTML = data;
            produit.remove();
            recalculerTotal();
        })
        .catch(error => console.error('Erreur:', error));
    });
});

function recalculerTotal() {
    const produits = document.querySelectorAll('.produit');
    let total = 0;
    produits.forEach(p => {
        const texte = p.textContent;
        const match = texte.match(/= (\d+(?:\.\d+)?) €/);
        if (match) total += parseFloat(match[1]);
    });
    const totalElem = document.getElementById('total');
    if (produits.length === 0) {
        if (totalElem) totalElem.remove();
        document.getElementById('panier').innerHTML = "<p style='text-align:center; color:white;'>Votre panier est vide.</p>";
    } else {
        totalElem.textContent = "Total : " + total.toFixed(2) + " €";
    }
}
