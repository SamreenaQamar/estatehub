// ============================================ //
// COMPLETE JAVASCRIPT FOR ESTATEHUB           //
// ALL ISSUES FIXED - READY TO USE             //
// ============================================ //

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
});
// Go to Home Page with filters
function goToHome(purpose, type = '') {
    let url = 'index.php?purpose=' + purpose;
    if(type) {
        url += '&type=' + type;
    }
    window.location.href = url;
}

// Set active tab based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('#propertyTabs .tab');
    const urlParams = new URLSearchParams(window.location.search);
    const currentPurpose = urlParams.get('purpose');
    
    // Remove active from all
    tabs.forEach(t => t.classList.remove('active'));
    
    // Set active based on URL
    if (currentPurpose === 'Sale') {
        document.querySelector('[data-type="buy"]').classList.add('active');
    } else if (currentPurpose === 'Rent') {
        document.querySelector('[data-type="rent"]').classList.add('active');
    } else if (currentPurpose === 'PG') {
        document.querySelector('[data-type="pg"]').classList.add('active');
    } else {
        document.querySelector('[data-type="buy"]').classList.add('active');
    }
    
    // Click event
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
// ============================================ //
// SET ACTIVE TAB & SCROLL ON PAGE LOAD        //
// ============================================ //

document.addEventListener('DOMContentLoaded', function() {
    // Set active tab based on URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const currentPurpose = urlParams.get('purpose');
    const tabs = document.querySelectorAll('#propertyTabs .tab');
    
    if (tabs.length > 0) {
        tabs.forEach(tab => tab.classList.remove('active'));
        
        if (currentPurpose === 'Sale') {
            document.querySelector('[data-type="buy"]')?.classList.add('active');
        } else if (currentPurpose === 'Rent') {
            document.querySelector('[data-type="rent"]')?.classList.add('active');
        } else if (currentPurpose === 'PG') {
            document.querySelector('[data-type="pg"]')?.classList.add('active');
        } else {
            // Default active
            document.querySelector('[data-type="buy"]')?.classList.add('active');
        }
    }
    
    // Scroll to filtered properties if coming from tab click
    if (sessionStorage.getItem('scrollToProperties') === 'true') {
        sessionStorage.removeItem('scrollToProperties');
        setTimeout(() => {
            const filteredSection = document.querySelector('.filtered-properties');
            if (filteredSection) {
                filteredSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 400);
    }
});

// ============================================ //
// PROPERTY DATA (12+ properties)              //
// ============================================ //

const properties = [
    {
        id: 1,
        title: "Modern 10 Marla House",
        location: "DHA Phase 6, Lahore",
        price: "5.2M",
        priceRent: null,
        type: "sale",
        beds: 5,
        baths: 6,
        area: "10 Marla",
        images: [
            "https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg",
            "https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg",
            "https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg",
            "https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg"
        ]
    },
    {
        id: 2,
        title: "Luxury Apartment",
        location: "Bahria Town, Karachi",
        price: "1.2M",
        priceRent: "/ Month",
        type: "rent",
        beds: 3,
        baths: 3,
        area: "2000 Sqft",
        images: [
            "https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg",
            "https://images.pexels.com/photos/2587056/pexels-photo-2587056.jpeg",
            "https://images.pexels.com/photos/2587052/pexels-photo-2587052.jpeg"
        ]
    },
    {
        id: 3,
        title: "Designer Villa",
        location: "F-10, Islamabad",
        price: "7.8M",
        priceRent: null,
        type: "sale",
        beds: 6,
        baths: 7,
        area: "1 Kanal",
        images: [
            "https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg",
            "https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg",
            "https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg"
        ]
    },
    {
        id: 4,
        title: "House with Double Kitchen",
        location: "Model Town, Lahore",
        price: "3.45M",
        priceRent: null,
        type: "sale",
        beds: 4,
        baths: 4,
        area: "8 Marla",
        images: [
            "https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg",
            "https://images.pexels.com/photos/280232/pexels-photo-280232.jpeg"
        ]
    },
    {
        id: 5,
        title: "Luxury Penthouse",
        location: "Clifton, Karachi",
        price: "2.5M",
        priceRent: "/ Month",
        type: "rent",
        beds: 4,
        baths: 5,
        area: "3500 Sqft",
        images: [
            "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg",
            "https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg"
        ]
    },
    {
        id: 6,
        title: "Modern Farm House",
        location: "Gulberg Greens, Islamabad",
        price: "9.5M",
        priceRent: null,
        type: "sale",
        beds: 5,
        baths: 6,
        area: "2 Kanal",
        images: [
            "https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg",
            "https://images.pexels.com/photos/208740/pexels-photo-208740.jpeg"
        ]
    },
    {
        id: 7,
        title: "Beach Front Villa",
        location: "Clifton, Karachi",
        price: "15M",
        priceRent: null,
        type: "sale",
        beds: 6,
        baths: 8,
        area: "3 Kanal",
        images: [
            "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg",
            "https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg"
        ]
    },
    {
        id: 8,
        title: "Corporate Office Space",
        location: "Blue Area, Islamabad",
        price: "8.5M",
        priceRent: "/ Month",
        type: "rent",
        beds: 0,
        baths: 4,
        area: "5000 Sqft",
        images: [
            "https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg",
            "https://images.pexels.com/photos/280232/pexels-photo-280232.jpeg"
        ]
    },
    {
        id: 9,
        title: "Residential Plot",
        location: "DHA, Lahore",
        price: "12M",
        priceRent: null,
        type: "sale",
        beds: 0,
        baths: 0,
        area: "10 Marla",
        images: [
            "https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg",
            "https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg"
        ]
    },
    {
        id: 10,
        title: "Luxury Apartment",
        location: "Gulberg, Lahore",
        price: "3.8M",
        priceRent: "/ Month",
        type: "rent",
        beds: 3,
        baths: 3,
        area: "1800 Sqft",
        images: [
            "https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg",
            "https://images.pexels.com/photos/2587056/pexels-photo-2587056.jpeg"
        ]
    },
    {
        id: 11,
        title: "Modern House",
        location: "Bahria Phase 7, Rawalpindi",
        price: "6.2M",
        priceRent: null,
        type: "sale",
        beds: 4,
        baths: 5,
        area: "12 Marla",
        images: [
            "https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg",
            "https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg"
        ]
    },
    {
        id: 12,
        title: "Penthouse Suite",
        location: "IT Tower, Karachi",
        price: "4.5M",
        priceRent: "/ Month",
        type: "rent",
        beds: 4,
        baths: 4,
        area: "2800 Sqft",
        images: [
            "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg",
            "https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg"
        ]
    }
];

// ============================================ //
// SLIDER FUNCTIONS                            //
// ============================================ //

let slideIndexes = {};

function initSlider(sliderId, imageCount) {
    slideIndexes[sliderId] = 0;
    updateDots(sliderId, imageCount);
}

function updateDots(sliderId, imageCount) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const dotsContainerId = sliderId.replace('slider', '');
    const dotsContainer = document.getElementById(`dots${dotsContainerId}`);
    if (!dotsContainer) return;
    
    dotsContainer.innerHTML = '';
    for (let i = 0; i < imageCount; i++) {
        const dot = document.createElement('div');
        dot.className = 'slider-dot' + (i === slideIndexes[sliderId] ? ' active' : '');
        dot.onclick = () => goToSlide(sliderId, i);
        dotsContainer.appendChild(dot);
    }
}

function goToSlide(sliderId, index) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const images = track.querySelectorAll('img');
    
    if (index < 0) index = 0;
    if (index >= images.length) index = images.length - 1;
    
    slideIndexes[sliderId] = index;
    track.style.transform = `translateX(-${index * 100}%)`;
    updateDots(sliderId, images.length);
}

window.prevSlide = function(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const images = track.querySelectorAll('img');
    let currentIndex = slideIndexes[sliderId] || 0;
    currentIndex--;
    if (currentIndex < 0) currentIndex = images.length - 1;
    goToSlide(sliderId, currentIndex);
}

window.nextSlide = function(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const images = track.querySelectorAll('img');
    let currentIndex = slideIndexes[sliderId] || 0;
    currentIndex++;
    if (currentIndex >= images.length) currentIndex = 0;
    goToSlide(sliderId, currentIndex);
}

// ============================================ //
// RENDER PROPERTIES FUNCTION                  //
// ============================================ //

function renderProperties() {
    const grid = document.getElementById('propertiesGrid');
    if (!grid) return;
    
    grid.innerHTML = properties.slice(0, 12).map(property => `
        <div class="property-card">
            <div class="card-image-slider" id="slider${property.id}">
                <div class="slider-container">
                    <div class="slider-track">
                        ${property.images.map(img => `<img src="${img}" alt="${property.title}">`).join('')}
                    </div>
                </div>
                <button class="slider-btn slider-prev" onclick="prevSlide('slider${property.id}')">‹</button>
                <button class="slider-btn slider-next" onclick="nextSlide('slider${property.id}')">›</button>
                <div class="slider-dots" id="dots${property.id}"></div>
            </div>
            <div class="card-tag ${property.type === 'sale' ? 'for-sale' : 'for-rent'}">
                ${property.type === 'sale' ? 'For Sale' : 'For Rent'}
            </div>
            <div class="wishlist-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
            </div>
            <div class="card-body">
                <h3>${property.title}</h3>
                <div class="card-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    ${property.location}
                </div>
                <div class="card-price">
                    PKR ${property.price}${property.type === 'sale' ? '' : `<span class="per-month">${property.priceRent}</span>`}
                </div>
                <div class="card-meta">
                    ${property.beds > 0 ? `<div class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>${property.beds} Beds</div>` : ''}
                    ${property.baths > 0 ? `<div class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>${property.baths} Baths</div>` : ''}
                    <div class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>${property.area}</div>
                </div>
                <a href="property-detail.php?id=${property.id}" class="view-detail-btn">View Details</a>
            </div>
        </div>
    `).join('');
    
    // Initialize sliders
    setTimeout(() => {
        for (let i = 1; i <= 12; i++) {
            const sliderId = `slider${i}`;
            const slider = document.getElementById(sliderId);
            if (slider) {
                const track = slider.querySelector('.slider-track');
                const images = track.querySelectorAll('img');
                initSlider(sliderId, images.length);
            }
        }
    }, 100);
}

// ============================================ //
// HERO TABS FUNCTIONALITY (Buy, Rent, PG)     //
// ============================================ //

// Extended property data for tabs (including PG)
const allPropertyData = [
    { title: 'Modern 10 Marla House', location: 'DHA Phase 6, Lahore', price: '5.2M', type: 'buy', beds: 5, baths: 6, area: '10 Marla', extra: 'Ready', 
      images: ['https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg', 'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg', 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg'] },
    { title: 'Luxury Apartment', location: 'Bahria Town, Karachi', price: '1.2M', type: 'rent', beds: 3, baths: 3, area: '2000 Sqft', extra: 'Furnished', 
      images: ['https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg', 'https://images.pexels.com/photos/2587056/pexels-photo-2587056.jpeg'] },
    { title: 'Designer Villa', location: 'F-10, Islamabad', price: '7.8M', type: 'buy', beds: 6, baths: 7, area: '1 Kanal', extra: 'Luxury', 
      images: ['https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg', 'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg'] },
    { title: 'Double Kitchen House', location: 'Model Town, Lahore', price: '3.45M', type: 'buy', beds: 4, baths: 4, area: '8 Marla', extra: '2 Kitchens', 
      images: ['https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg', 'https://images.pexels.com/photos/280232/pexels-photo-280232.jpeg'] },
    { title: 'Luxury Penthouse', location: 'Clifton, Karachi', price: '2.5M', type: 'rent', beds: 4, baths: 5, area: '3500 Sqft', extra: 'Sea View', 
      images: ['https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg', 'https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg'] },
    { title: 'Modern Farm House', location: 'Gulberg Greens, Islamabad', price: '9.5M', type: 'buy', beds: 5, baths: 6, area: '2 Kanal', extra: 'Pool+Garden', 
      images: ['https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg', 'https://images.pexels.com/photos/208740/pexels-photo-208740.jpeg'] },
    { title: 'Cozy Studio', location: 'Gulberg, Lahore', price: '450K', type: 'rent', beds: 2, baths: 2, area: '1200 Sqft', extra: 'Fully Furnished', 
      images: ['https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg'] },
    { title: 'Shared PG Room', location: 'F-11, Islamabad', price: '25K', type: 'pg', beds: 1, baths: 1, area: '500 Sqft', extra: 'Meals Included', 
      images: ['https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg'] },
    { title: 'Luxury PG', location: 'DHA, Lahore', price: '45K', type: 'pg', beds: 2, baths: 2, area: '800 Sqft', extra: 'AC & WiFi', 
      images: ['https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg'] },
    { title: 'Beach Front Villa', location: 'DHA, Karachi', price: '15.8M', type: 'buy', beds: 7, baths: 8, area: '4 Kanal', extra: 'Beach View', 
      images: ['https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg'] },
    { title: 'Corporate Apartment', location: 'Clifton, Karachi', price: '3.5M', type: 'rent', beds: 4, baths: 4, area: '2800 Sqft', extra: 'Luxury', 
      images: ['https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg'] },
    { title: 'Budget PG', location: 'Township, Lahore', price: '15K', type: 'pg', beds: 1, baths: 1, area: '400 Sqft', extra: 'Basic', 
      images: ['https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg'] }
];


let tabSlideIndexes = {};

function initSliderTab(sliderId, imageCount) {
    tabSlideIndexes[sliderId] = 0;
    updateDotsTab(sliderId, imageCount);
}

function updateDotsTab(sliderId, imageCount) {
    const dotsContainer = document.getElementById(`dots_${sliderId}`);
    if (!dotsContainer) return;
    
    dotsContainer.innerHTML = '';
    for (let i = 0; i < imageCount; i++) {
        const dot = document.createElement('div');
        dot.className = 'slider-dot' + (i === tabSlideIndexes[sliderId] ? ' active' : '');
        dot.onclick = () => goToSlideTab(sliderId, i);
        dotsContainer.appendChild(dot);
    }
}

function goToSlideTab(sliderId, index) {
    const slider = document.getElementById(sliderId);
    if(!slider) return;
    const track = slider.querySelector('.slider-track');
    if(!track) return;
    const images = track.querySelectorAll('img');
    
    if (index < 0) index = 0;
    if (index >= images.length) index = images.length - 1;
    
    tabSlideIndexes[sliderId] = index;
    track.style.transform = `translateX(-${index * 100}%)`;
    updateDotsTab(sliderId, images.length);
}

window.prevSlideTab = function(sliderId) {
    const slider = document.getElementById(sliderId);
    if(!slider) return;
    const track = slider.querySelector('.slider-track');
    if(!track) return;
    const images = track.querySelectorAll('img');
    let currentIndex = tabSlideIndexes[sliderId] || 0;
    currentIndex--;
    if (currentIndex < 0) currentIndex = images.length - 1;
    goToSlideTab(sliderId, currentIndex);
}

window.nextSlideTab = function(sliderId) {
    const slider = document.getElementById(sliderId);
    if(!slider) return;
    const track = slider.querySelector('.slider-track');
    if(!track) return;
    const images = track.querySelectorAll('img');
    let currentIndex = tabSlideIndexes[sliderId] || 0;
    currentIndex++;
    if (currentIndex >= images.length) currentIndex = 0;
    goToSlideTab(sliderId, currentIndex);
}

function filterPropertiesByType(type) {
    const propertiesGrid = document.querySelector('.properties-grid');
    if (!propertiesGrid) return;
    
    let filteredProperties;
    if (type === 'buy') {
        filteredProperties = allPropertyData.filter(p => p.type === 'buy');
    } else if (type === 'rent') {
        filteredProperties = allPropertyData.filter(p => p.type === 'rent');
    } else if (type === 'pg') {
        filteredProperties = allPropertyData.filter(p => p.type === 'pg');
    } else {
        filteredProperties = allPropertyData;
    }
    
    propertiesGrid.innerHTML = '';
    
    filteredProperties.forEach((property, idx) => {
        const propertyId = `tab_${type}_${idx}`;
        const card = document.createElement('div');
        card.className = 'property-card';
        card.innerHTML = `
            <div class="card-image-slider" id="slider_${propertyId}">
                <div class="slider-container">
                    <div class="slider-track">
                        ${property.images.map(img => `<img src="${img}" alt="${property.title}">`).join('')}
                        ${property.images.length < 3 ? property.images.map(img => `<img src="${img}" alt="${property.title}">`).join('') : ''}
                    </div>
                </div>
                <button class="slider-btn slider-prev" onclick="prevSlideTab('slider_${propertyId}')">‹</button>
                <button class="slider-btn slider-next" onclick="nextSlideTab('slider_${propertyId}')">›</button>
                <div class="slider-dots" id="dots_slider_${propertyId}"></div>
            </div>
            <div class="card-tag ${property.type === 'buy' ? 'for-sale' : (property.type === 'rent' ? 'for-rent' : 'for-pg')}">
                ${property.type === 'buy' ? 'For Sale' : (property.type === 'rent' ? 'For Rent' : 'PG Available')}
            </div>
            <div class="wishlist-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
            </div>
            <div class="card-body">
                <h3>${property.title}</h3>
                <div class="card-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    ${property.location}
                </div>
                <div class="card-price">
                    PKR ${property.price}${property.type === 'rent' ? '<span class="per-month">/ Month</span>' : (property.type === 'pg' ? '<span class="per-month">/ Month</span>' : '')}
                </div>
                <div class="card-meta">
                    <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>${property.beds} Beds</span>
                    <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>${property.baths} Baths</span>
                    <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>${property.area}</span>
                    <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>${property.extra}</span>
                </div>
                <a href="property-detail.php" class="view-detail-btn">View Details</a>
            </div>
        `;
        propertiesGrid.appendChild(card);
        
        setTimeout(() => {
            const sliderElement = document.getElementById(`slider_${propertyId}`);
            if (sliderElement) {
                const track = sliderElement.querySelector('.slider-track');
                const images = track.querySelectorAll('img');
                initSliderTab(`slider_${propertyId}`, images.length);
            }
        }, 50);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize slider for pre-existing property cards (if any)
    for (let i = 1; i <= 17; i++) {
        const sliderId = `slider${i}`;
        const slider = document.getElementById(sliderId);
        if (slider) {
            const track = slider.querySelector('.slider-track');
            if (track) {
                const images = track.querySelectorAll('img');
                initSlider(sliderId, images.length);
            }
        }
    }
    
    // Setup hero tabs
    const heroTabs = document.querySelectorAll('.hero-tabs .tab-btn');
    if (heroTabs.length > 0) {
        heroTabs.forEach(btn => {
            btn.addEventListener('click', function() {
                heroTabs.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const tabText = this.innerText.toLowerCase();
                filterPropertiesByType(tabText);
            });
        });
    }
    
    // Load buy properties by default if hero tabs exist
    if (heroTabs.length > 0) {
        filterPropertiesByType('buy');
    } else {
        renderProperties();
    }
});
function toggleWishlist(propertyId, btn) {
    btn.classList.toggle('active');

    fetch('add-to-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'property_id=' + propertyId
    }).catch(function() {
        btn.classList.toggle('active');
    });
}
// ===== Find Your Property - Widget Interactions =====
document.addEventListener('DOMContentLoaded', function() {
    // Ripple effect for Search button
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    }

    // Reset button: clear form
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('filterForm').reset();
            // اگر چاہیں تو بغیر فلٹر کے listing.php پر بھیجیں
            // window.location.href = 'listing.php';
        });
    }
});