/**
 * MediBuddy Hoofdapplicatie JavaScript
 * Beheert sitefunctionaliteit, productweergave en interacties
 */

// Wacht tot DOM volledig is geladen voor code-uitvoering
document.addEventListener('DOMContentLoaded', function() {
    
    // =====================
    // GEGEVENSDEFINITIES
    // =====================
    
    // Categorieëngegevens voor productweergave op hoofdpagina
    const categories = [
        {"icon": "💊", "name": "Pain Relievers"},
        {"icon": "🩺", "name": "Vitamins & Supplements"},
        {"icon": "💧", "name": "Skin Care"},
        {"icon": "🌸", "name": "Allergy Medicines"},
        {"icon": "📜", "name": "Prescription Drugs"}
    ];
    
    // Productgegevens voor weergave in productsectie
    const products = [
        {"image": ".//Img/Vitamine.png", "name": "Vitamin C Complex", "desc": "High strength with zinc", "rating": 4, "reviews": 124, "price": "$19.99"},
        {"image": ".//Img/pijnstiller.png", "name": "Pain Relief Extra", "desc": "Fast-acting tablets", "rating": 5, "reviews": 89, "price": "$12.99"},
        {"image": ".//Img/allergy.png", "name": "Hydrating Cream", "desc": "24-hour moisture protection", "rating": 4, "reviews": 56, "price": "$24.99"},
        {"image": ".//Img/allergy.png", "name": "Allergy Spray", "desc": "Fast-acting nasal spray", "rating": 4, "reviews": 78, "price": "$15.99"}
    ];
    
    // =====================
    // PAGINA-INITIALISATIE
    // =====================
    
    // Initialiseer themafunctionaliteit voor licht/donker modus
    initTheme();
    
    // Initialiseer hoofdpaginacomponenten zoals categorieën en producten
    initializePageComponents();
    
    // Zet paginaovergangen op voor vloeiende navigatie
    setupPageTransitions();
    
    // =====================
    // THEMABEHEER
    // =====================
    
    // Initialiseer themafunctionaliteit met gebruikersvoorkeuren
    function initTheme() {
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        
        if (!themeToggle || !themeIcon) return;
        
        // Creëer overlay voor vloeiende themaovergangen
        const overlay = document.createElement('div');
        overlay.className = 'theme-transition-overlay';
        document.body.appendChild(overlay);
        
        // Initialiseer thema op basis van opgeslagen voorkeur
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            setTheme('dark');
        } else {
            setTheme('light');
        }
        
        // Wissel thema bij klikken met animatie-effect
        themeToggle.addEventListener('click', function() {
            const isDark = document.body.classList.contains('dark-theme');
            const newTheme = isDark ? 'light' : 'dark';
            
            // Animatieklassen
            themeToggle.classList.add('animate');
            overlay.classList.add('active');
            document.body.classList.add('theme-transitioning');
            
            setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Verwijder animatieklassen na overgang
            setTimeout(() => {
                themeToggle.classList.remove('animate');
                overlay.classList.remove('active');
                document.body.classList.remove('theme-transitioning');
            }, 500);
        });
        
        // Luister naar systeemthemawijzigingen indien geen handmatige voorkeur ingesteld
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
    
    // Stel thema in en werk icoon bij
    function setTheme(theme) {
        const themeIcon = document.getElementById('theme-icon');
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
            if (themeIcon) themeIcon.textContent = '☀️';
        } else {
            document.body.classList.remove('dark-theme');
            if (themeIcon) themeIcon.textContent = '🌙';
        }
    }
    
    // =====================
    // COMPONENTWEERGAVE
    // =====================
    
    // Render categorieën als container bestaat op pagina
    function renderCategories() {
        const container = document.getElementById('categories-container');
        if (!container) return;
        
        // Genereer categoriekaarten dynamisch vanuit arrays
        categories.forEach(category => {
            const categoryCard = document.createElement('div');
            categoryCard.className = 'category-card animate-on-scroll';
            
            categoryCard.innerHTML = `
                <div class="category-icon">${category.icon}</div>
                <p class="category-name">${category.name}</p>
            `;
            
            container.appendChild(categoryCard);
        });
    }
    
    // Render producten als container bestaat op pagina
    function renderProducts() {
        const container = document.getElementById('products-container');
        if (!container) return;
        
        // Genereer productkaarten dynamisch vanuit productarray
        products.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card animate-on-scroll';
            
            // Creëer weergave van beoordelingssterren
            let starsHtml = '';
            for (let i = 0; i < 5; i++) {
                starsHtml += i < product.rating ? "⭐" : "☆";
            }
            
            productCard.innerHTML = `
                <img src="${product.image}" class="product-image">
                <h3 class="product-title">${product.name}</h3>
                <p class="product-description">${product.desc}</p>
                <div class="product-rating">
                    ${starsHtml}
                    <span class="rating-count">(${product.reviews})</span>
                </div>
                <div class="product-footer">
                    <span class="product-price">${product.price}</span>
                    <button class="add-to-cart">Add to Cart</button>
                </div>
            `;
            
            container.appendChild(productCard);
        });
        
        // Configureer gebeurtenissen voor winkelwagenknoppen
        setupAddToCartButtons();
    }
    
    // =====================
    // GEBEURTENISAFHANDELAARS
    // =====================
    
    // Verwerk klikken op "Toevoegen aan winkelwagen"-knoppen
    function setupAddToCartButtons() {
        const addButtons = document.querySelectorAll('.add-to-cart');
        addButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Voorkom standaard link-navigatiegedrag
                e.preventDefault();
                
                // Verkrijg productgegevens uit omringende HTML-elementen
                const card = this.closest('.product-card');
                const productName = card.querySelector('.product-title').textContent;
                const productPrice = card.querySelector('.product-price').textContent;
                
                // Toon bevestiging en werk winkelwagenbadge bij
                updateCartBadge(1);
                showToast(`${productName} (${productPrice}) added to cart!`);
                
                // Voeg subtiele animatie toe voor feedback
                button.classList.add('added');
                setTimeout(() => button.classList.remove('added'), 700);
            });
        });
    }
    
    // Werk winkelwagenbadge bij met nieuwe aantal
    function updateCartBadge(count) {
        const badge = document.querySelector('.cart-badge');
        if (!badge) return;
        
        const currentCount = parseInt(badge.textContent);
        badge.textContent = currentCount + count;
        
        // Voeg animatie toe aan badge
        badge.classList.add('pulse');
        setTimeout(() => badge.classList.remove('pulse'), 500);
    }
    
    // Toon meldingsbericht onderaan het scherm
    function showToast(message) {
        // Creëer toast-element als het niet bestaat
        let toast = document.getElementById('toast-notification');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast-notification';
            document.body.appendChild(toast);
            
            // Voeg toast-stijlen toe indien niet aanwezig
            const style = document.createElement('style');
            style.textContent = `
                #toast-notification {
                    position: fixed;
                    bottom: 2rem;
                    right: 2rem;
                    background: var(--accent-color);
                    color: white;
                    padding: 1rem;
                    border-radius: 0.5rem;
                    box-shadow: var(--shadow-md);
                    z-index: 1000;
                    opacity: 0;
                    transform: translateY(1rem);
                    transition: opacity 0.3s, transform 0.3s;
                }
                #toast-notification.show {
                    opacity: 1;
                    transform: translateY(0);
                }
            `;
            document.head.appendChild(style);
        }
        
        // Stel bericht in en toon
        toast.textContent = message;
        toast.classList.add('show');
        
        // Verberg na vertraging voor tijdelijke melding
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Configureer paginaovergangen voor vloeiende navigatie
    function setupPageTransitions() {
        // Creëer transitie-overlay element indien niet aanwezig
        if (!document.querySelector('.page-transition')) {
            const pageTransition = document.createElement('div');
            pageTransition.className = 'page-transition';
            document.body.appendChild(pageTransition);
        }
        
        // Verwerk interne linkklikken met overgangsanimatie
        document.addEventListener('click', e => {
            const link = e.target.closest('a[href]:not([target="_blank"]):not([href^="#"])');
            if (link && link.hostname === window.location.hostname) {
                e.preventDefault();
                
                const transition = document.querySelector('.page-transition');
                transition.classList.add('active');
                
                // Navigeer na overgang
                setTimeout(() => {
                    window.location.href = link.href;
                }, 300);
            }
        });
    }
    
    // Configureer scroll-animaties voor geleidelijke contentweergave
    function setupScrollAnimations() {
        const animateOnScroll = () => {
            // Voeg animatie toe aan elementen bij scrollen
            document.querySelectorAll('.animate-on-scroll:not(.animated)').forEach(element => {
                const position = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight - 150;
                
                if (position < screenPosition) {
                    element.classList.add('animated');
                }
            });
        };
        
        // Voeg scrollevent toe en voer eenmaal uit bij laden
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Direct uitvoeren bij laden
    }
    
    // =====================
    // INITIALISATIE
    // =====================
    
    // Hoofdinitialisatiefunctie voor alle paginacomponenten
    function initializePageComponents() {
        // Render inhoudssecties met dynamische data
        renderCategories();
        renderProducts();
        
        // Configureer interacties voor gebruikerservaring
        setupScrollAnimations();
        
        // Voeg animatieklassen toe aan koppen
        document.querySelectorAll('.section-title').forEach(el => {
            el.classList.add('animate-on-scroll');
        });
    }
});
