const express = require('express');
const fs = require('fs');
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('.')); // Pour servir votre fichier HTML depuis le dossier courant

// Chemin du fichier JSON
const DATA_FILE = path.join(__dirname, 'data/inscriptions.json');

// Initialiser le fichier JSON s'il n'existe pas
if (!fs.existsSync(DATA_FILE)) {
    fs.writeFileSync(DATA_FILE, JSON.stringify([], null, 2));
}

// Lire toutes les inscriptions
function getInscriptions() {
    const data = fs.readFileSync(DATA_FILE, 'utf8');
    return JSON.parse(data);
}

// Sauvegarder une inscription
function saveInscription(inscription) {
    const inscriptions = getInscriptions();
    inscription.id = Date.now();
    inscription.date = new Date().toISOString();
    inscriptions.push(inscription);
    fs.writeFileSync(DATA_FILE, JSON.stringify(inscriptions, null, 2));
    return inscription;
}

// 📌 API : Récupérer toutes les inscriptions
app.get('/api/inscriptions', (req, res) => {
    const inscriptions = getInscriptions();
    res.json(inscriptions);
});

// 📌 API : Ajouter une nouvelle inscription
app.post('/api/inscriptions', (req, res) => {
    const newInscription = req.body;
    
    // Validation basique
    if (!newInscription.whatsapp || !newInscription.motivation) {
        return res.status(400).json({ error: 'Champs requis manquants' });
    }
    
    const saved = saveInscription(newInscription);
    console.log(`✅ Nouvelle inscription : ${saved.whatsapp} (ID: ${saved.id})`);
    res.json({ success: true, inscription: saved });
});

// 📌 API : Supprimer une inscription
app.delete('/api/inscriptions/:id', (req, res) => {
    const id = parseInt(req.params.id);
    let inscriptions = getInscriptions();
    inscriptions = inscriptions.filter(i => i.id !== id);
    fs.writeFileSync(DATA_FILE, JSON.stringify(inscriptions, null, 2));
    res.json({ success: true });
});

// 📌 API : Exporter en CSV
app.get('/api/export/csv', (req, res) => {
    const inscriptions = getInscriptions();
    if (inscriptions.length === 0) {
        return res.status(404).json({ error: 'Aucune donnée à exporter' });
    }
    
    const headers = ['ID', 'Date', 'Source', 'Motivation', 'Import', 'Produits', 'Budget', 'Sexe', 'Commune', 'WhatsApp', 'Types'];
    const rows = inscriptions.map(i => [
        i.id,
        i.date,
        i.source,
        i.motivation,
        i.import,
        i.produits,
        i.budget,
        i.sexe,
        i.commune || '',
        i.whatsapp,
        (i.types || []).join(', ')
    ]);
    
    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',') + '\n';
    });
    
    res.setHeader('Content-Type', 'text/csv');
    res.setHeader('Content-Disposition', 'attachment; filename=inscriptions_zouwor.csv');
    res.send(csv);
});

// Servir la page admin
app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, 'admin.html'));
});

// Démarrer le serveur
app.listen(PORT, () => {
    console.log(`\n🚀 Serveur Zouwor démarré !`);
    console.log(`📝 Formulaire : http://localhost:${PORT}/index.html`);
    console.log(`👑 Administration : http://localhost:${PORT}/admin.html`);
    console.log(`📊 Données sauvegardées dans : ${DATA_FILE}\n`);
});