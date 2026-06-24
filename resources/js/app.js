import './bootstrap';
import 'leaflet/dist/leaflet.css';
import * as Turbo from '@hotwired/turbo';
import { createIcons, icons } from 'lucide';

Turbo.start();

const defaultReportData = {
  All: {
    registered: 56,
    eligible: 42,
    deferred: 9,
    ineligible: 5,
    screened: 54,
    subtext: 'all blood types',
    screenedLabel: 'All screenings',
    ratios: { eligible: 75, deferred: 16, ineligible: 9 },
    share: null,
    trend: [6, 7, 8, 7, 10, 8, 10],
  },
  'O+': {
    registered: 12,
    eligible: 9,
    deferred: 2,
    ineligible: 1,
    screened: 12,
    subtext: 'O+ donors',
    screenedLabel: 'O+ screenings',
    ratios: { eligible: 75, deferred: 17, ineligible: 8 },
    share: 21.4,
    trend: [1, 2, 2, 1, 2, 2, 2],
  },
  'O-': {
    registered: 4,
    eligible: 3,
    deferred: 1,
    ineligible: 0,
    screened: 4,
    subtext: 'O- donors',
    screenedLabel: 'O- screenings',
    ratios: { eligible: 75, deferred: 25, ineligible: 0 },
    share: 7.1,
    trend: [0, 1, 0, 1, 0, 1, 1],
  },
  'A+': {
    registered: 10,
    eligible: 7,
    deferred: 2,
    ineligible: 1,
    screened: 10,
    subtext: 'A+ donors',
    screenedLabel: 'A+ screenings',
    ratios: { eligible: 70, deferred: 20, ineligible: 10 },
    share: 17.9,
    trend: [1, 1, 2, 1, 2, 1, 2],
  },
  'A-': {
    registered: 5,
    eligible: 4,
    deferred: 1,
    ineligible: 0,
    screened: 5,
    subtext: 'A- donors',
    screenedLabel: 'A- screenings',
    ratios: { eligible: 80, deferred: 20, ineligible: 0 },
    share: 8.9,
    trend: [1, 0, 1, 1, 0, 1, 1],
  },
  'B+': {
    registered: 8,
    eligible: 6,
    deferred: 1,
    ineligible: 1,
    screened: 8,
    subtext: 'B+ donors',
    screenedLabel: 'B+ screenings',
    ratios: { eligible: 75, deferred: 13, ineligible: 12 },
    share: 14.3,
    trend: [1, 1, 1, 1, 2, 1, 1],
  },
  'B-': {
    registered: 4,
    eligible: 3,
    deferred: 1,
    ineligible: 0,
    screened: 4,
    subtext: 'B- donors',
    screenedLabel: 'B- screenings',
    ratios: { eligible: 75, deferred: 25, ineligible: 0 },
    share: 7.1,
    trend: [0, 1, 1, 0, 1, 0, 1],
  },
  'AB+': {
    registered: 7,
    eligible: 5,
    deferred: 1,
    ineligible: 1,
    screened: 6,
    subtext: 'AB+ donors',
    screenedLabel: 'AB+ screenings',
    ratios: { eligible: 71, deferred: 14, ineligible: 15 },
    share: 12.5,
    trend: [1, 1, 1, 1, 1, 1, 1],
  },
  'AB-': {
    registered: 6,
    eligible: 5,
    deferred: 0,
    ineligible: 1,
    screened: 5,
    subtext: 'AB- donors',
    screenedLabel: 'AB- screenings',
    ratios: { eligible: 83, deferred: 0, ineligible: 17 },
    share: 10.7,
    trend: [1, 0, 1, 1, 1, 1, 1],
  },
};

const reportColors = {
  All: '#b70100',
  'O+': '#b70100',
  'O-': '#9a452a',
  'A+': '#775043',
  'A-': '#ba1a1a',
  'B+': '#ff9473',
  'B-': '#ffb59f',
  'AB+': '#623e32',
  'AB-': '#e60000',
};

const defaultReportDistribution = [
  { type: 'O+', share: 21.4, color: reportColors['O+'] },
  { type: 'O-', share: 7.1, color: reportColors['O-'] },
  { type: 'A+', share: 17.9, color: reportColors['A+'] },
  { type: 'A-', share: 8.9, color: reportColors['A-'] },
  { type: 'B+', share: 14.3, color: reportColors['B+'] },
  { type: 'B-', share: 7.1, color: reportColors['B-'] },
  { type: 'AB+', share: 12.5, color: reportColors['AB+'] },
  { type: 'AB-', share: 10.7, color: reportColors['AB-'] },
];

const trendLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Today'];

const tabLoadControllers = new Map();

function refreshIcons() {
  createIcons({
    icons,
    attrs: {
      'stroke-width': 1.9,
    },
  });
}

function prepareTurboOptOuts() {
  document.querySelectorAll('a[href*="/reports/"], a[download]').forEach((link) => {
    link.setAttribute('data-turbo', 'false');
  });
}

function formatNumber(value) {
  return new Intl.NumberFormat('en-US').format(value);
}

function parsedDataset(element, key, fallback) {
  if (!element) {
    return fallback;
  }

  const cacheKey = `_${key}`;
  if (element[cacheKey] !== undefined) {
    return element[cacheKey];
  }

  try {
    element[cacheKey] = JSON.parse(element.dataset[key] || '');
  } catch (error) {
    element[cacheKey] = fallback;
  }

  return element[cacheKey];
}

function reportDataFor(container) {
  return parsedDataset(container, 'reportStats', defaultReportData);
}

function reportDistributionFor(container) {
  return parsedDataset(container, 'reportDistribution', defaultReportDistribution);
}

function conicGradientForDistribution(distribution) {
  let accumulated = 0;
  const segments = distribution
    .filter((segment) => Number(segment.share) > 0)
    .map((segment) => {
      const start = accumulated;
      accumulated += Number(segment.share);
      return `${segment.color || reportColors[segment.type] || '#b70100'} ${start}% ${accumulated}%`;
    });

  if (segments.length === 0) {
    return 'conic-gradient(#e7e5e4 0 100%)';
  }

  if (accumulated < 100) {
    segments.push(`#e7e5e4 ${accumulated}% 100%`);
  }

  return `conic-gradient(${segments.join(', ')})`;
}

function formatPercent(value) {
  const number = Number(value) || 0;
  return Number.isInteger(number) ? String(number) : number.toFixed(1);
}

function openMobileNavDrawer() {
  const drawer = document.querySelector('[data-mobile-drawer]');
  const backdrop = document.querySelector('[data-mobile-drawer-backdrop]');
  if (!drawer || !backdrop) {
    return;
  }

  window.clearTimeout(drawer._mobileCloseTimer);
  drawer.hidden = false;
  backdrop.hidden = false;
  drawer.setAttribute('aria-hidden', 'false');
  document.documentElement.classList.add('has-mobile-drawer-open');
  document.querySelectorAll('[data-mobile-menu-open]').forEach((button) => {
    button.setAttribute('aria-expanded', 'true');
  });

  window.requestAnimationFrame(() => {
    drawer.classList.add('is-open');
    backdrop.classList.add('is-open');
  });
}

function closeMobileNavDrawer({ immediate = false } = {}) {
  const drawer = document.querySelector('[data-mobile-drawer]');
  const backdrop = document.querySelector('[data-mobile-drawer-backdrop]');
  if (!drawer || !backdrop) {
    return;
  }

  drawer.classList.remove('is-open');
  backdrop.classList.remove('is-open');
  drawer.setAttribute('aria-hidden', 'true');
  document.documentElement.classList.remove('has-mobile-drawer-open');
  document.querySelectorAll('[data-mobile-menu-open]').forEach((button) => {
    button.setAttribute('aria-expanded', 'false');
  });

  const finish = () => {
    drawer.hidden = true;
    backdrop.hidden = true;
  };

  window.clearTimeout(drawer._mobileCloseTimer);
  if (immediate) {
    finish();
    return;
  }

  drawer._mobileCloseTimer = window.setTimeout(finish, 220);
}

function initMobilePortalNav() {
  const drawer = document.querySelector('[data-mobile-drawer]');
  const backdrop = document.querySelector('[data-mobile-drawer-backdrop]');
  if (!drawer || !backdrop) {
    return;
  }

  const isOpen = drawer.classList.contains('is-open');
  drawer.hidden = !isOpen;
  backdrop.hidden = !isOpen;
  drawer.setAttribute('aria-hidden', String(!isOpen));
  document.querySelectorAll('[data-mobile-menu-open]').forEach((button) => {
    button.setAttribute('aria-expanded', String(isOpen));
  });
}

function clearStaleScrollLocks() {
  if (!document.querySelector('.modal-shell.is-open')) {
    document.documentElement.classList.remove('has-modal-open');
    document.body.classList.remove('has-modal-open');
    delete document.documentElement.dataset.modalScrollTop;
    delete document.documentElement.dataset.modalLockCount;
  }

  if (!document.querySelector('[data-mobile-drawer].is-open')) {
    document.documentElement.classList.remove('has-mobile-drawer-open');
  }

  if (!document.querySelector('[data-notification-drawer]:not([hidden])')) {
    document.documentElement.classList.remove('has-notification-drawer-open');
  }
}

function initializeDynamicWidgets() {
  clearStaleScrollLocks();
  prepareTurboOptOuts();
  initAutoDismiss();
  initMobilePortalNav();
  initLoginCarousel();
  initScheduleLeafletMaps();
  initAdminLeafletMaps();
  initScheduleWizard();
  initReportFilters();
  initNotificationFilters();
  initNotificationDrawer();
  initInventoryFilters();
  syncActiveTabInputs();
  refreshIcons();

  const activePanel = document.querySelector('[data-portal-content]')?.dataset.activePanel;
  if (activePanel === 'map') {
    window.setTimeout(() => initAdminLeafletMaps(), 60);
  }
}

function initAutoDismiss() {
  document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
    if (element.dataset.dismissReady === 'true') {
      return;
    }

    element.dataset.dismissReady = 'true';
    const delay = Number(element.dataset.autoDismiss || 2000);

    window.setTimeout(() => {
      element.classList.add('is-dismissing');
      window.setTimeout(() => element.remove(), 240);
    }, delay);
  });
}

function currentPortalTab() {
  return window.location.hash ? window.location.hash.slice(1) : null;
}

function syncActiveTabInputs() {
  const content = document.querySelector('[data-portal-content]');
  const active = content?.dataset.activePanel || currentPortalTab();
  if (!active) {
    return;
  }

  document.querySelectorAll('form').forEach((form) => {
    let input = form.querySelector('input[name="_active_tab"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = '_active_tab';
      form.appendChild(input);
    }
    input.value = active;
  });
}

function showPortalPanel(panelName, { push = false } = {}) {
  const content = document.querySelector('[data-portal-content]');
  const shell = document.querySelector('[data-portal-shell]');
  if (!content || !shell) {
    return;
  }

  content.dataset.activePanel = panelName;
  document.querySelectorAll('[data-portal-panel]').forEach((panel) => {
    panel.hidden = panel.dataset.portalPanel !== panelName;
    panel.classList.toggle('is-active', panel.dataset.portalPanel === panelName);
  });

  document.querySelectorAll('[data-portal-tab]').forEach((tab) => {
    tab.classList.toggle('is-active', tab.dataset.portalTab === panelName);
  });

  const activeTab = document.querySelector(`[data-portal-tab="${CSS.escape(panelName)}"]`);
  const title = activeTab?.dataset.title || panelName.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
  document.querySelector('[data-topbar-title]')?.replaceChildren(title);

  if (push && window.location.hash !== `#${panelName}`) {
    history.pushState({ panel: panelName }, '', `${window.location.pathname}#${panelName}`);
  }

  syncActiveTabInputs();
  refreshIcons();
}

async function loadPortalPanel(tab, { push = true } = {}) {
  const content = document.querySelector('[data-portal-content]');
  if (!content || !tab?.dataset?.portalTab) {
    return;
  }

  const panelName = tab.dataset.portalTab;
  const existingPanel = content.querySelector(`[data-portal-panel="${CSS.escape(panelName)}"]`);
  if (existingPanel) {
    showPortalPanel(panelName, { push });
    return;
  }

  showPortalPanel(content.dataset.activePanel || panelName, { push: false });

  let loadingPanel = content.querySelector(`[data-loading-panel="${CSS.escape(panelName)}"]`);
  if (!loadingPanel) {
    loadingPanel = document.createElement('div');
    loadingPanel.className = 'portal-panel portal-loading';
    loadingPanel.dataset.portalPanel = panelName;
    loadingPanel.dataset.loadingPanel = panelName;
    loadingPanel.innerHTML = '<div class="card p-6"><p class="eyebrow">Loading</p><p class="mt-3 text-stone-600">Preparing this workspace...</p></div>';
    content.appendChild(loadingPanel);
  }
  showPortalPanel(panelName, { push });

  const previousController = tabLoadControllers.get(panelName);
  if (previousController) {
    previousController.abort();
  }
  const controller = new AbortController();
  tabLoadControllers.set(panelName, controller);

  try {
    const response = await fetch(tab.dataset.panelUrl, {
      credentials: 'same-origin',
      headers: {
        'X-Portal-Panel': '1',
        'X-Requested-With': 'XMLHttpRequest',
      },
      signal: controller.signal,
    });

    if (!response.ok) {
      throw new Error(`Panel request failed (${response.status})`);
    }

    const html = await response.text();
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const nextPanel = parsed.querySelector(`[data-portal-panel="${CSS.escape(panelName)}"]`);
    const panel = nextPanel || parsed.querySelector('[data-portal-panel]');

    if (!panel) {
      throw new Error('Panel markup was not found');
    }

    loadingPanel.replaceWith(document.importNode(panel, true));
    showPortalPanel(panelName, { push: false });
    initializeDynamicWidgets();
  } catch (error) {
    if (error.name === 'AbortError') {
      return;
    }
    loadingPanel.innerHTML = `<div class="alert">This tab could not load. ${error.message}</div>`;
  } finally {
    tabLoadControllers.delete(panelName);
  }
}

function initPortalShell() {
  const shell = document.querySelector('[data-portal-shell]');
  const content = document.querySelector('[data-portal-content]');
  if (!shell || !content || shell.dataset.portalReady === 'true') {
    return;
  }

  shell.dataset.portalReady = 'true';
  const hashPanel = currentPortalTab();
  const initialPanel = hashPanel || content.dataset.activePanel;
  const initialTab = initialPanel ? document.querySelector(`[data-portal-tab="${CSS.escape(initialPanel)}"]`) : null;

  if (initialTab && !content.querySelector(`[data-portal-panel="${CSS.escape(initialPanel)}"]`)) {
    loadPortalPanel(initialTab, { push: false });
  } else if (initialPanel) {
    showPortalPanel(initialPanel, { push: false });
  }
}

function initLoginCarousel() {
  document.querySelectorAll('[data-login-carousel]').forEach((carousel) => {
    if (carousel.dataset.carouselReady === 'true') {
      return;
    }

    carousel.dataset.carouselReady = 'true';
    const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
    if (slides.length < 2) {
      return;
    }

    let index = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
    let timer = null;

    const setSlide = (nextIndex) => {
      index = (nextIndex + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => {
        slide.classList.toggle('is-active', slideIndex === index);
      });
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === index);
        dot.setAttribute('aria-selected', dotIndex === index ? 'true' : 'false');
      });
    };

    const restart = () => {
      window.clearInterval(timer);
      timer = window.setInterval(() => setSlide(index + 1), 5200);
      carousel._carouselTimer = timer;
    };

    carousel.addEventListener('click', (event) => {
      const control = event.target.closest('[data-carousel-control]');
      const dot = event.target.closest('[data-carousel-dot]');
      if (control) {
        setSlide(index + (control.dataset.carouselControl === 'prev' ? -1 : 1));
        restart();
      }
      if (dot) {
        setSlide(Number(dot.dataset.carouselDot));
        restart();
      }
    });

    setSlide(index);
    restart();
  });
}

function formattedDate(value) {
  if (!value) {
    return 'Select a date';
  }

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(date);
}

function fallbackScheduleCenters() {
  return [
    {
      id: 'CTR-SR-LAGUNA',
      name: 'PRC Laguna Chapter - Santa Rosa Branch',
      lat: 14.31554,
      lng: 121.11104,
      type: 'Santa Rosa Red Cross',
      tag: 'Santa Rosa, Laguna',
      address: 'Rotary Lane, Brgy. Tagapo, City of Santa Rosa, Laguna',
      pin: '14.31554, 121.11104',
    },
  ];
}

function normalizeScheduleCenter(center = {}) {
  const lat = Number(center.lat ?? center.latitude ?? 14.31554);
  const lng = Number(center.lng ?? center.longitude ?? 121.11104);

  return {
    id: String(center.id || 'CTR-SR-LAGUNA'),
    name: String(center.name || 'PRC Laguna Chapter - Santa Rosa Branch'),
    address: String(center.address || 'Rotary Lane, Brgy. Tagapo, City of Santa Rosa, Laguna'),
    type: String(center.type || center.center_type || 'Santa Rosa Red Cross'),
    tag: String(center.tag || center.city || center.area || center.type || 'Santa Rosa, Laguna'),
    lat,
    lng,
    pin: center.pin || `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
  };
}

function escapeMapPopup(value) {
  const node = document.createElement('span');
  node.textContent = String(value ?? '');
  return node.innerHTML;
}

function scheduleCentersFor(mapElement) {
  const centers = parsedDataset(mapElement, 'centers', fallbackScheduleCenters());
  return (Array.isArray(centers) && centers.length > 0 ? centers : fallbackScheduleCenters())
    .map(normalizeScheduleCenter)
    .filter((center) => Number.isFinite(center.lat) && Number.isFinite(center.lng));
}

function invalidateScheduleMap(wizard) {
  const mapElement = wizard?.querySelector('[data-schedule-leaflet-map]');
  if (!mapElement?._scheduleLeaflet?.map) {
    return;
  }

  window.setTimeout(() => {
    mapElement._scheduleLeaflet.map.invalidateSize();
  }, 80);
}

function updateScheduleMapSelection(wizard, center, { pan = true } = {}) {
  const mapElement = wizard?.querySelector('[data-schedule-leaflet-map]');
  const instance = mapElement?._scheduleLeaflet;
  if (!instance?.map || !center?.id) {
    return;
  }

  instance.selectedCenterId = center.id;
  instance.markers.forEach((marker, markerId) => {
    const selected = markerId === center.id;
    marker.setStyle({
      radius: selected ? 12 : 8,
      color: selected ? '#8b0000' : '#b70100',
      fillColor: selected ? '#e60000' : '#c40000',
      fillOpacity: selected ? 0.9 : 0.72,
      weight: selected ? 3 : 2,
    });

    if (selected) {
      marker.bringToFront();
    }
  });

  if (pan) {
    instance.map.setView([center.lat, center.lng], Math.max(instance.map.getZoom(), 13), {
      animate: true,
    });
  }
}

async function initScheduleLeafletMaps() {
  const mapElements = [...document.querySelectorAll('[data-schedule-leaflet-map]')];
  if (mapElements.length === 0) {
    return;
  }

  const pendingMapElements = mapElements.filter((mapElement) => {
    if (mapElement.dataset.leafletReady === 'true') {
      invalidateScheduleMap(mapElement.closest('[data-schedule-wizard]'));
      return false;
    }

    return mapElement.dataset.leafletReady !== 'loading';
  });

  if (pendingMapElements.length === 0) {
    return;
  }

  pendingMapElements.forEach((mapElement) => {
    mapElement.dataset.leafletReady = 'loading';
  });

  const L = await import('leaflet');

  pendingMapElements.forEach((mapElement) => {
    if (!mapElement.isConnected) {
      return;
    }

    const centers = scheduleCentersFor(mapElement);
    if (centers.length === 0) {
      delete mapElement.dataset.leafletReady;
      return;
    }

    const selectedId = mapElement.dataset.selectedCenter || 'CTR-SR-LAGUNA';
    const selected = centers.find((center) => center.id === selectedId)
      || centers.find((center) => center.id === 'CTR-SR-LAGUNA')
      || centers[0];

    const map = L.map(mapElement, { zoomControl: true }).setView([selected.lat, selected.lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    const markers = new Map();
    const wizard = mapElement.closest('[data-schedule-wizard]');
    centers.forEach((center) => {
      const isSelected = center.id === selected.id;
      const marker = L.circleMarker([center.lat, center.lng], {
        radius: isSelected ? 12 : 8,
        color: isSelected ? '#8b0000' : '#b70100',
        fillColor: isSelected ? '#e60000' : '#c40000',
        fillOpacity: isSelected ? 0.9 : 0.72,
        weight: isSelected ? 3 : 2,
      })
        .addTo(map)
        .bindPopup(`<strong>${center.name}</strong><br>${center.address}`);

      marker.on('click', () => {
        selectScheduleCenter(wizard, center);
        marker.openPopup();
      });

      markers.set(center.id, marker);
    });

    mapElement._scheduleLeaflet = {
      map,
      markers,
      centers,
      selectedCenterId: selected.id,
    };
    mapElement.dataset.leafletReady = 'true';

    selectScheduleCenter(wizard, selected, { pan: false });
    invalidateScheduleMap(wizard);
  });
}

function adminCentersFor(mapElement) {
  const centers = parsedDataset(mapElement, 'adminLeafletCenters', fallbackScheduleCenters());
  return (Array.isArray(centers) && centers.length > 0 ? centers : fallbackScheduleCenters())
    .map(normalizeScheduleCenter)
    .filter((center) => Number.isFinite(center.lat) && Number.isFinite(center.lng));
}

function updateAdminMapSelection(mapElement, center) {
  const frame = mapElement.closest('.admin-map-frame');
  if (!frame || !center) {
    return;
  }

  frame.querySelector('[data-admin-map-name]')?.replaceChildren(center.name);
  frame.querySelector('[data-admin-map-address]')?.replaceChildren(center.address);
  frame.querySelector('[data-admin-map-type]')?.replaceChildren(center.type);
}

async function initAdminLeafletMaps() {
  const maps = [...document.querySelectorAll('[data-admin-leaflet-map]')];
  if (maps.length === 0) {
    return;
  }

  const pending = maps.filter((mapElement) => mapElement.dataset.adminLeafletReady !== 'true' && mapElement.dataset.adminLeafletReady !== 'loading');
  maps.filter((mapElement) => mapElement.dataset.adminLeafletReady === 'true').forEach((mapElement) => {
    window.setTimeout(() => mapElement._adminLeaflet?.map?.invalidateSize(), 80);
  });
  if (pending.length === 0) {
    return;
  }

  pending.forEach((mapElement) => {
    mapElement.dataset.adminLeafletReady = 'loading';
  });

  const L = await import('leaflet');

  pending.forEach((mapElement) => {
    if (!mapElement.isConnected) {
      return;
    }

    const centers = adminCentersFor(mapElement);
    if (centers.length === 0) {
      delete mapElement.dataset.adminLeafletReady;
      return;
    }

    const preferred = centers.find((center) => center.id === 'CTR-SR-LAGUNA') || centers[0];
    const map = L.map(mapElement, { zoomControl: true, scrollWheelZoom: true }).setView([preferred.lat, preferred.lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    const markers = [];
    centers.forEach((center) => {
      const marker = L.circleMarker([center.lat, center.lng], {
        radius: center.id === preferred.id ? 12 : 8,
        color: '#8b0000',
        fillColor: '#e60000',
        fillOpacity: 0.85,
        weight: 2,
      })
        .addTo(map)
        .bindPopup(`<strong>${escapeMapPopup(center.name)}</strong><br>${escapeMapPopup(center.address)}`);

      marker.on('click', () => {
        markers.forEach(({ marker: otherMarker, center: otherCenter }) => {
          const selected = otherCenter.id === center.id;
          otherMarker.setStyle({ radius: selected ? 12 : 8, weight: selected ? 3 : 2, fillOpacity: selected ? 0.92 : 0.72 });
        });
        updateAdminMapSelection(mapElement, center);
        marker.openPopup();
      });
      markers.push({ marker, center });
    });

    if (centers.length > 1) {
      map.fitBounds(centers.map((center) => [center.lat, center.lng]), { padding: [36, 36], maxZoom: 13 });
    }

    mapElement._adminLeaflet = { map, markers };
    mapElement.dataset.adminLeafletReady = 'true';
    updateAdminMapSelection(mapElement, preferred);
    window.setTimeout(() => map.invalidateSize(), 80);
  });
}

function updateScheduleReview(wizard) {
  const form = wizard.querySelector('[data-schedule-form]');
  const service = form?.querySelector('[data-schedule-service-input]')?.value || 'Donor Appointment';
  const centerName = form?.querySelector('[data-schedule-center-input]')?.value || 'PRC Laguna Chapter - Santa Rosa Branch';
  const centerAddress = wizard.querySelector('[data-schedule-center-address]')?.textContent || '';
  const dateValue = form?.querySelector('[data-schedule-date-input]')?.value || '';
  const timeValue = form?.querySelector('[data-schedule-time-input]')?.value || '10:30 AM';
  const dateLabel = formattedDate(dateValue);
  const summary = service === 'Blood Request'
    ? [
      form?.querySelector('[name="patient_name"]')?.value || 'Patient details',
      form?.querySelector('[name="blood_type"]')?.value || 'blood type pending',
      form?.querySelector('[name="units_needed"]')?.value ? `${form.querySelector('[name="units_needed"]').value} units` : 'units pending',
    ].join(' - ')
    : [
      form?.querySelector('[name="donor_full_name"]')?.value || 'Donor details',
      form?.querySelector('[name="donor_blood_type"]')?.value || 'blood type pending',
      form?.querySelector('[name="donor_contact_number"]')?.value || 'contact pending',
    ].join(' - ');

  wizard.querySelector('[data-schedule-date-label]')?.replaceChildren(dateLabel);
  wizard.querySelector('[data-schedule-review-center]')?.replaceChildren(centerName);
  wizard.querySelector('[data-schedule-review-address]')?.replaceChildren(centerAddress);
  wizard.querySelector('[data-schedule-review-date]')?.replaceChildren(dateLabel);
  wizard.querySelector('[data-schedule-review-time]')?.replaceChildren(timeValue);
  wizard.querySelector('[data-schedule-review-service]')?.replaceChildren(service);
  wizard.querySelector('[data-schedule-review-form-summary]')?.replaceChildren(summary);
}

function setScheduleService(wizard, service) {
  if (!wizard) {
    return;
  }

  const selectedService = service || wizard.querySelector('[data-schedule-service-input]')?.value || 'Donor Appointment';
  const serviceInput = wizard.querySelector('[data-schedule-service-input]');
  if (serviceInput) {
    serviceInput.value = selectedService;
  }

  wizard.querySelectorAll('[data-schedule-service]').forEach((button) => {
    button.classList.toggle('is-selected', button.dataset.scheduleService === selectedService);
  });

  wizard.querySelectorAll('[data-schedule-service-form]').forEach((panel) => {
    const isActive = panel.dataset.scheduleServiceForm === selectedService;
    panel.hidden = !isActive;
    panel.querySelectorAll('input, select, textarea').forEach((field) => {
      field.disabled = !isActive;
    });
  });

  updateScheduleReview(wizard);
}

function selectScheduleCenter(wizard, center, { pan = true } = {}) {
  if (!wizard || !center?.name) {
    return;
  }

  const selectedCenter = normalizeScheduleCenter(center);

  wizard.querySelector('[data-schedule-center-input]').value = selectedCenter.name;
  wizard.querySelector('[data-schedule-center-id-input]').value = selectedCenter.id;
  wizard.querySelector('[data-schedule-center-name]')?.replaceChildren(selectedCenter.name);
  wizard.querySelector('[data-schedule-center-address]')?.replaceChildren(selectedCenter.address);
  wizard.querySelector('[data-schedule-center-tag]')?.replaceChildren(selectedCenter.tag);
  wizard.querySelector('[data-schedule-center-pin]')?.replaceChildren(selectedCenter.pin);

  const select = wizard.querySelector('[data-schedule-center-select]');
  if (select) {
    select.value = selectedCenter.name;
  }

  wizard.querySelectorAll('[data-schedule-center-choice]').forEach((choice) => {
    choice.classList.toggle('is-selected', choice.dataset.centerId === selectedCenter.id);
  });

  updateScheduleMapSelection(wizard, selectedCenter, { pan });
  updateScheduleReview(wizard);
}

function showScheduleStep(wizard, step) {
  const nextStep = Math.min(6, Math.max(1, Number(step) || 1));
  wizard.dataset.scheduleCurrentStep = String(nextStep);

  wizard.querySelectorAll('[data-schedule-panel]').forEach((panel) => {
    panel.hidden = Number(panel.dataset.schedulePanel) !== nextStep;
  });

  wizard.querySelectorAll('[data-schedule-step-indicator]').forEach((indicator) => {
    const indicatorStep = Number(indicator.dataset.scheduleStepIndicator);
    indicator.classList.toggle('is-active', indicatorStep <= nextStep);
    indicator.classList.toggle('is-current', indicatorStep === nextStep);
  });

  setScheduleService(wizard);
  updateScheduleReview(wizard);
  if (nextStep === 4) {
    initScheduleLeafletMaps();
    invalidateScheduleMap(wizard);
  }
  refreshIcons();
}

function initScheduleWizard() {
  document.querySelectorAll('[data-schedule-wizard]').forEach((wizard) => {
    if (wizard.dataset.scheduleReady === 'true') {
      return;
    }

    wizard.dataset.scheduleReady = 'true';
    setScheduleService(wizard);
    showScheduleStep(wizard, Number(wizard.dataset.scheduleCurrentStep || 1));
  });
}

function applyReportFilter(type) {
  const container = document.querySelector('[data-report-dashboard]');
  if (!container) {
    return;
  }

  const dashboardData = reportDataFor(container);
  const distribution = reportDistributionFor(container);
  const data = dashboardData[type] || dashboardData.All || defaultReportData.All;

  container.querySelectorAll('[data-report-filter]').forEach((button) => {
    button.classList.toggle('is-active', button.dataset.reportFilter === type);
  });

  container.querySelector('[data-report-value="registered"]')?.replaceChildren(formatNumber(data.registered));
  container.querySelector('[data-report-value="eligible"]')?.replaceChildren(formatNumber(data.eligible));
  container.querySelector('[data-report-value="deferred"]')?.replaceChildren(formatNumber(data.deferred));
  container.querySelector('[data-report-value="ineligible"]')?.replaceChildren(formatNumber(data.ineligible));
  container.querySelector('[data-report-value="screened"]')?.replaceChildren(formatNumber(data.screened));
  container.querySelector('[data-report-subtext="registered"]')?.replaceChildren(data.subtext);
  container.querySelector('[data-report-subtext="eligible"]')?.replaceChildren(`${data.ratios.eligible}% eligible`);
  container.querySelector('[data-report-subtext="deferred"]')?.replaceChildren(`${data.ratios.deferred}% deferred`);
  container.querySelector('[data-report-subtext="ineligible"]')?.replaceChildren(`${data.ratios.ineligible}% ineligible`);
  container.querySelector('[data-report-subtext="screened"]')?.replaceChildren(data.screenedLabel);

  Object.entries(data.ratios).forEach(([key, value]) => {
    const row = container.querySelector(`[data-report-ratio="${key}"]`);
    if (!row) {
      return;
    }
    row.querySelector('[data-report-ratio-value]')?.replaceChildren(`${value}%`);
    const bar = row.querySelector('[data-report-ratio-bar]');
    if (bar) {
      bar.style.width = `${value}%`;
    }
  });

  const donut = container.querySelector('[data-report-donut]');
  if (donut) {
    donut.dataset.donutType = type;
    if (type === 'All') {
      donut.style.background = conicGradientForDistribution(distribution);
      donut.dataset.tooltip = 'All blood types: 100%';
      donut.dataset.donutShare = '100';
    } else {
      const share = data.share;
      donut.style.background = `conic-gradient(${reportColors[type]} 0 ${share}%, #e7e5e4 ${share}% 100%)`;
      donut.dataset.tooltip = `${type}: ${share}`;
      donut.dataset.donutShare = String(share);
    }
  }

  const legend = container.querySelector('[data-report-legend]');
  if (legend && type !== 'All') {
    legend.innerHTML = `<span><span class="legend-dot" style="background:${reportColors[type]}"></span> ${type}: ${data.share}%</span><span><span class="legend-dot" style="background:#e7e5e4"></span> Other Blood Types: ${Number(100 - data.share).toFixed(data.share % 1 ? 1 : 0)}%</span>`;
  } else if (legend) {
    legend.innerHTML = distribution
      .map((segment) => `<span><span class="legend-dot" style="background:${segment.color || reportColors[segment.type]}"></span> ${segment.type}: ${formatPercent(segment.share)}%</span>`)
      .join('');
  }

  const trendMax = Math.max(1, ...data.trend);
  container.querySelectorAll('[data-report-trend-bar]').forEach((bar, index) => {
    const value = data.trend[index] || 0;
    bar.style.height = `${Math.max(8, Math.round((value / trendMax) * 100))}%`;
    bar.dataset.tooltip = `${trendLabels[index] || 'Day'} registered: ${formatNumber(value)}`;
  });

  const rows = [...container.querySelectorAll('[data-donor-blood]')];
  let visibleRows = 0;
  rows.forEach((row) => {
    const isDetail = row.hasAttribute('data-donor-detail-row');
    const matches = type === 'All' || row.dataset.donorBlood === type;
    if (!isDetail) {
      visibleRows += matches ? 1 : 0;
    }
    row.hidden = !matches || isDetail;
  });

  const empty = container.querySelector('[data-report-empty]');
  if (empty) {
    empty.hidden = type === 'All' || visibleRows > 0;
  }

  const notice = container.querySelector('[data-report-notice]');
  if (notice) {
    notice.hidden = type === 'All';
    notice.textContent = `Showing ${type} only across KPIs, charts, and donor records.`;
  }
}

function initReportFilters() {
  document.querySelectorAll('[data-report-dashboard]').forEach((container) => {
    if (container.dataset.reportReady === 'true') {
      return;
    }
    container.dataset.reportReady = 'true';
    const legend = container.querySelector('[data-report-legend]');
    if (legend) {
      legend.dataset.defaultLegend = legend.innerHTML;
    }
    applyReportFilter(container.querySelector('[data-report-filter].is-active')?.dataset.reportFilter || 'All');
  });
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function updateNotificationDetail(item) {
  const panel = item?.closest('[data-notification-center]')?.querySelector('[data-notification-detail]');
  if (!panel || !item) {
    return;
  }

  panel.querySelector('[data-note-detail-tag]')?.replaceChildren(item.dataset.noteTag);
  panel.querySelector('[data-note-detail-title]')?.replaceChildren(item.dataset.noteTitle);
  panel.querySelector('[data-note-detail-time]')?.replaceChildren(item.dataset.noteTime);
  panel.querySelector('[data-note-detail-body]')?.replaceChildren(item.dataset.noteBody);
  panel.querySelector('[data-note-detail-id]')?.replaceChildren(item.dataset.noteId);
  panel.querySelector('[data-note-detail-status]')?.replaceChildren(item.dataset.noteStatus);
}

function updateNotificationCounts(container, countOverride = null) {
  if (!container) {
    return;
  }

  const count = countOverride ?? [...container.querySelectorAll('[data-notification-item]')]
    .filter((item) => item.dataset.noteRead !== 'true')
    .length;

  container.querySelectorAll('[data-unread-count]').forEach((element) => element.replaceChildren(String(count)));
  document.querySelectorAll('[data-notification-global-count]').forEach((badge) => {
    badge.replaceChildren(String(count));
    badge.hidden = count === 0;
  });
}

function refreshAllNotificationCounts() {
  document.querySelectorAll('[data-notification-center]').forEach((container) => updateNotificationCounts(container));
}

function refreshAllNotificationFilters() {
  document.querySelectorAll('[data-notification-center]').forEach((container) => {
    applyNotificationFilter(container.dataset.notificationFilter || 'all', container, {
      selectedItem: container.querySelector('[data-notification-item].is-selected:not([hidden])'),
    });
  });
}

function showNotificationError(container, message = '') {
  const element = container?.querySelector('[data-notification-error]');
  if (!element) {
    return;
  }

  element.hidden = message === '';
  element.replaceChildren(message);
}

function setNotificationReadState(item, read) {
  if (!item) {
    return;
  }

  item.dataset.noteRead = read ? 'true' : 'false';
  const filters = new Set((item.dataset.noteFilters || '').split(',').filter(Boolean));
  filters.add('all');
  if (read) {
    filters.delete('unread');
  } else {
    filters.add('unread');
  }
  item.dataset.noteFilters = [...filters].join(',');
  item.classList.toggle('is-read', read);

  const state = read ? 'Read' : 'Unread';
  const currentTime = item.dataset.noteTime || `${state} - now`;
  const nextTime = /^(Unread|Read)/i.test(currentTime)
    ? currentTime.replace(/^(Unread|Read)/i, state)
    : `${state} - ${currentTime}`;
  item.dataset.noteTime = nextTime;
  item.querySelector('[data-note-time]')?.replaceChildren(nextTime);
}

function setMatchingNotificationReadState(notificationId, read) {
  if (!notificationId) {
    return [];
  }

  const selector = `[data-notification-item][data-notification-id="${CSS.escape(notificationId)}"]`;
  const items = [...document.querySelectorAll(selector)];
  items.forEach((item) => setNotificationReadState(item, read));
  return items;
}

function formBodyForPayload(payload = {}) {
  const body = new URLSearchParams();
  Object.entries(payload).forEach(([key, value]) => {
    if (Array.isArray(value)) {
      value.forEach((item) => body.append(`${key}[]`, item));
      return;
    }

    if (value !== undefined && value !== null) {
      body.append(key, value);
    }
  });

  return body;
}

async function postNotificationJson(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: formBodyForPayload(payload),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.message || `Notification request failed (${response.status})`);
  }

  return response.json();
}

async function markNotificationItemRead(item) {
  if (!item || item.dataset.noteRead === 'true') {
    return;
  }

  const container = item.closest('[data-notification-center]');
  const template = container?.dataset.readUrlTemplate;
  const notificationId = item.dataset.notificationId;

  if (!container || !template || !notificationId) {
    return;
  }

  showNotificationError(container);
  setMatchingNotificationReadState(notificationId, true);
  refreshAllNotificationCounts();
  refreshAllNotificationFilters();

  try {
    const data = await postNotificationJson(
      template.replace('__NOTIFICATION_ID__', encodeURIComponent(notificationId)),
      {}
    );
    if (typeof data.unread_count === 'number') {
      updateNotificationCounts(container, data.unread_count);
    }
  } catch (error) {
    setMatchingNotificationReadState(notificationId, false);
    refreshAllNotificationCounts();
    refreshAllNotificationFilters();
    showNotificationError(container, 'Could not save this read status. Please try again.');
    console.error(error);
  }
}

async function markVisibleNotificationsRead(container) {
  if (!container?.dataset.readAllUrl) {
    return;
  }

  const items = [...container.querySelectorAll('[data-notification-item]')]
    .filter((item) => !item.hidden && item.dataset.noteRead !== 'true');
  const ids = items.map((item) => item.dataset.notificationId).filter(Boolean);

  if (ids.length === 0) {
    refreshAllNotificationCounts();
    return;
  }

  ids.forEach((notificationId) => setMatchingNotificationReadState(notificationId, true));
  showNotificationError(container);
  refreshAllNotificationCounts();
  refreshAllNotificationFilters();

  try {
    const data = await postNotificationJson(container.dataset.readAllUrl, { ids });
    if (typeof data.unread_count === 'number') {
      updateNotificationCounts(container, data.unread_count);
    }
  } catch (error) {
    ids.forEach((notificationId) => setMatchingNotificationReadState(notificationId, false));
    refreshAllNotificationCounts();
    refreshAllNotificationFilters();
    showNotificationError(container, 'Could not mark these notifications as read. Please try again.');
    console.error(error);
  }
}

function applyNotificationFilter(filter, container = document.querySelector('[data-notification-center]'), { selectedItem = null } = {}) {
  if (!container) {
    return;
  }

  container.dataset.notificationFilter = filter;
  container.querySelectorAll('[data-notification-filter]').forEach((button) => {
    button.classList.toggle('is-active', button.dataset.notificationFilter === filter);
  });

  const items = [...container.querySelectorAll('[data-notification-item]')];
  let firstVisible = null;
  let visibleCount = 0;
  items.forEach((item) => {
    const tags = (item.dataset.noteFilters || '').split(',');
    const matches = filter === 'all' || tags.includes(filter);
    item.hidden = !matches;
    if (matches) {
      visibleCount += 1;
    }
    if (matches && !firstVisible) {
      firstVisible = item;
    }
  });

  const empty = container.querySelector('[data-notification-empty]');
  if (empty) {
    empty.hidden = items.length === 0 || visibleCount > 0;
  }

  const currentSelection = selectedItem && !selectedItem.hidden
    ? selectedItem
    : container.querySelector('[data-notification-item].is-selected:not([hidden])');
  const nextSelection = currentSelection || firstVisible;
  items.forEach((item) => item.classList.toggle('is-selected', item === nextSelection));
  if (nextSelection) {
    updateNotificationDetail(nextSelection);
  }

  updateNotificationCounts(container);
}

function initNotificationFilters() {
  document.querySelectorAll('[data-notification-center]').forEach((container) => {
    if (container.dataset.notificationReady === 'true') {
      return;
    }
    container.dataset.notificationReady = 'true';
    container.querySelectorAll('[data-notification-item]').forEach((item) => {
      item.classList.toggle('is-read', item.dataset.noteRead === 'true');
    });
    applyNotificationFilter(container.dataset.notificationFilter || 'all', container);
  });
}

function openNotificationDrawer() {
  const drawer = document.querySelector('[data-notification-drawer]');
  if (!drawer) {
    return;
  }
  drawer.hidden = false;
  drawer.setAttribute('aria-hidden', 'false');
  document.documentElement.classList.add('has-notification-drawer-open');
  refreshIcons();
}

function closeNotificationDrawer() {
  const drawer = document.querySelector('[data-notification-drawer]');
  if (!drawer) {
    return;
  }
  drawer.hidden = true;
  drawer.setAttribute('aria-hidden', 'true');
  document.documentElement.classList.remove('has-notification-drawer-open');
}

function resetCampaignForm() {
  const form = document.querySelector('[data-campaign-form]');
  if (!form) {
    return;
  }

  form.reset();
  const imageField = form.querySelector('[data-campaign-field="imageUrl"]');
  if (imageField?.defaultValue) {
    imageField.value = imageField.defaultValue;
  }
  form.querySelector('[data-campaign-form-title]')?.replaceChildren('Post Campaign');
  const submit = form.querySelector('[data-campaign-submit]');
  if (submit) {
    submit.disabled = false;
    submit.replaceChildren('Post Campaign');
  }
  form.querySelector('[data-campaign-edit-note]')?.setAttribute('hidden', '');
}

function fillCampaignForm(dataset) {
  const form = document.querySelector('[data-campaign-form]');
  if (!form) {
    return;
  }

  form.querySelector('[data-campaign-form-title]')?.replaceChildren('Edit Campaign');
  form.querySelectorAll('[data-campaign-field]').forEach((field) => {
    const key = field.dataset.campaignField;
    if (key && Object.prototype.hasOwnProperty.call(dataset, key)) {
      field.value = dataset[key] || '';
    }
  });
  form.querySelector('[data-campaign-edit-note]')?.removeAttribute('hidden');
  const submit = form.querySelector('[data-campaign-submit]');
  if (submit) {
    submit.disabled = true;
    submit.replaceChildren('Save Disabled');
  }
}

function initNotificationDrawer() {
  const drawer = document.querySelector('[data-notification-drawer]');
  if (!drawer || drawer.dataset.drawerReady === 'true') {
    return;
  }
  drawer.dataset.drawerReady = 'true';
  closeNotificationDrawer();
}

function applyInventoryFilters(workspace) {
  if (!workspace) {
    return;
  }

  const search = (workspace.querySelector('[data-inventory-search]')?.value || '').trim().toLowerCase();
  const blood = workspace.querySelector('[data-inventory-blood-filter]')?.value || 'all';
  const status = workspace.querySelector('[data-inventory-status-filter]')?.value || 'all';
  const sort = workspace.querySelector('[data-inventory-sort-filter]')?.value || 'date-desc';
  const body = workspace.querySelector('[data-inventory-table-body]');
  const empty = workspace.querySelector('[data-inventory-empty]');
  const rows = [...workspace.querySelectorAll('[data-inventory-row]')];

  const visibleRows = rows.filter((row) => {
    const haystack = row.textContent.toLowerCase();
    const matchesSearch = search === '' || haystack.includes(search);
    const matchesBlood = blood === 'all' || row.dataset.inventoryBlood === blood;
    const matchesStatus = status === 'all' || row.dataset.inventoryStatus === status;
    row.hidden = !(matchesSearch && matchesBlood && matchesStatus);
    return !row.hidden;
  });

  visibleRows.sort((left, right) => {
    if (sort === 'alpha-asc' || sort === 'alpha-desc') {
      const result = (left.dataset.inventorySort || '').localeCompare(right.dataset.inventorySort || '');
      return sort === 'alpha-asc' ? result : -result;
    }
    const result = Number(left.dataset.inventoryDate || 0) - Number(right.dataset.inventoryDate || 0);
    return sort === 'date-asc' ? result : -result;
  });

  visibleRows.forEach((row) => body?.insertBefore(row, empty || null));
  if (empty) {
    empty.hidden = visibleRows.length !== 0;
  }
}

function closeInventoryFilters(except = null) {
  document.querySelectorAll('[data-inventory-filter-menu]').forEach((menu) => {
    if (menu !== except) {
      menu.hidden = true;
      menu.closest('.inventory-filter-wrap')?.querySelector('[data-inventory-filter-toggle]')?.setAttribute('aria-expanded', 'false');
    }
  });
}

function initInventoryFilters() {
  document.querySelectorAll('[data-inventory-workspace]').forEach((workspace) => {
    if (workspace.dataset.inventoryReady === 'true') {
      return;
    }
    workspace.dataset.inventoryReady = 'true';
    applyInventoryFilters(workspace);
  });
}

async function openNewDonation(action) {
  const inventoryTab = document.querySelector('[data-portal-tab="inventory"]');
  if (!inventoryTab) {
    return;
  }

  await loadPortalPanel(inventoryTab, { push: true });
  window.setTimeout(() => openModal(document.querySelector('#add-entry-modal')), 0);
}

function lockModalPageScroll() {
  const portalMain = document.querySelector('.portal-main');

  if (!document.documentElement.dataset.modalLockCount) {
    document.documentElement.dataset.modalLockCount = '0';
  }

  const lockCount = Number(document.documentElement.dataset.modalLockCount);
  document.documentElement.dataset.modalLockCount = String(lockCount + 1);

  if (lockCount > 0) {
    return;
  }

  document.documentElement.dataset.modalScrollTop = String(portalMain?.scrollTop || window.scrollY || 0);
  document.documentElement.classList.add('has-modal-open');
  document.body.classList.add('has-modal-open');
}

function unlockModalPageScroll() {
  const lockCount = Number(document.documentElement.dataset.modalLockCount || 0);
  const nextCount = Math.max(0, lockCount - 1);
  document.documentElement.dataset.modalLockCount = String(nextCount);

  if (nextCount > 0 || document.querySelector('.modal-shell.is-open')) {
    return;
  }

  const portalMain = document.querySelector('.portal-main');
  const scrollTop = Number(document.documentElement.dataset.modalScrollTop || 0);
  document.documentElement.classList.remove('has-modal-open');
  document.body.classList.remove('has-modal-open');
  delete document.documentElement.dataset.modalScrollTop;
  delete document.documentElement.dataset.modalLockCount;

  if (portalMain) {
    portalMain.scrollTop = scrollTop;
  }
}

function mountModalForViewport(modal) {
  if (!modal || modal.parentElement === document.body) {
    return;
  }

  const placeholder = document.createComment(`modal:${modal.id || 'anonymous'}`);
  modal.parentNode?.insertBefore(placeholder, modal);
  modal._modalPlaceholder = placeholder;
  document.body.appendChild(modal);
}

function restoreModalMount(modal) {
  const placeholder = modal?._modalPlaceholder;

  if (!modal || !placeholder) {
    return;
  }

  if (placeholder.parentNode) {
    placeholder.parentNode.insertBefore(modal, placeholder);
    placeholder.remove();
  }

  delete modal._modalPlaceholder;
}

function openModal(modal) {
  if (!modal) {
    return;
  }

  const wasOpen = modal.classList.contains('is-open');
  mountModalForViewport(modal);
  modal.hidden = false;
  modal.classList.add('is-open');
  if (!wasOpen) {
    lockModalPageScroll();
  }
  refreshIcons();
  modal.querySelector('input, select, textarea, button')?.focus({ preventScroll: true });
}

function closeModal(modal) {
  if (!modal) {
    return;
  }
  const wasOpen = modal.classList.contains('is-open');
  modal.classList.remove('is-open');
  modal.hidden = true;
  restoreModalMount(modal);
  if (wasOpen) {
    unlockModalPageScroll();
  }
}

function conicPercentForEvent(target, event) {
  const rect = target.getBoundingClientRect();
  const dx = event.clientX - (rect.left + rect.width / 2);
  const dy = event.clientY - (rect.top + rect.height / 2);
  const degrees = (Math.atan2(dy, dx) * 180) / Math.PI;

  return ((degrees + 90 + 360) % 360) / 3.6;
}

function donutTooltip(target, event) {
  if (!target.matches('[data-report-donut]')) {
    return null;
  }

  const percent = conicPercentForEvent(target, event);
  const selectedType = target.dataset.donutType || 'All';
  const container = target.closest('[data-report-dashboard]');
  const dashboardData = reportDataFor(container);

  if (selectedType !== 'All') {
    const share = Number(target.dataset.donutShare || dashboardData[selectedType]?.share || 0);
    if (percent <= share) {
      return {
        text: `${selectedType} : ${formatPercent(share)}`,
        color: reportColors[selectedType],
      };
    }

    const otherShare = Number(100 - share);
    return {
      text: `Other Blood Types : ${formatPercent(otherShare)}`,
      color: '#6f6864',
    };
  }

  let accumulated = 0;
  for (const segment of reportDistributionFor(container)) {
    accumulated += Number(segment.share);
    if (percent <= accumulated) {
      return {
        text: `${segment.type} : ${formatPercent(segment.share)}`,
        color: segment.color,
      };
    }
  }

  const distribution = reportDistributionFor(container);
  const fallback = distribution[distribution.length - 1] || defaultReportDistribution[0];
  return {
    text: `${fallback.type} : ${formatPercent(fallback.share)}`,
    color: fallback.color,
  };
}

function tooltipPayload(target, event) {
  return donutTooltip(target, event) || {
    text: target?.dataset?.tooltip || '',
    color: target?.dataset?.tooltipColor || '#c40000',
  };
}

function showTooltip(target, event) {
  const { text, color } = tooltipPayload(target, event);
  if (!text) {
    return;
  }

  let tooltip = document.querySelector('[data-floating-tooltip]');
  if (!tooltip) {
    tooltip = document.createElement('div');
    tooltip.dataset.floatingTooltip = '';
    tooltip.className = 'floating-tooltip';
    document.body.appendChild(tooltip);
  }

  tooltip.textContent = text;
  tooltip.style.setProperty('--tooltip-color', color);
  tooltip.hidden = false;
  moveTooltip(event);
}

function moveTooltip(event) {
  const tooltip = document.querySelector('[data-floating-tooltip]');
  if (!tooltip || tooltip.hidden) {
    return;
  }
  const target = event.target.closest('[data-tooltip]');
  if (target) {
    const { text, color } = tooltipPayload(target, event);
    tooltip.textContent = text;
    tooltip.style.setProperty('--tooltip-color', color);
  }
  const offset = 16;
  const width = tooltip.offsetWidth || 150;
  const height = tooltip.offsetHeight || 44;
  const left = Math.min(event.clientX + offset, window.innerWidth - width - 12);
  const top = Math.min(event.clientY + offset, window.innerHeight - height - 12);
  tooltip.style.transform = `translate(${left}px, ${top}px)`;
}

function hideTooltip() {
  const tooltip = document.querySelector('[data-floating-tooltip]');
  if (tooltip) {
    tooltip.hidden = true;
  }
}

function initializePage() {
  initPortalShell();
  initializeDynamicWidgets();
}

document.addEventListener('DOMContentLoaded', initializePage);
document.addEventListener('turbo:load', initializePage);
document.addEventListener('turbo:render', initializePage);
document.addEventListener('turbo:frame-render', initializePage);
document.addEventListener('turbo:before-cache', () => {
  document.querySelectorAll('[data-portal-shell]').forEach((shell) => delete shell.dataset.portalReady);
  document.querySelectorAll('[data-schedule-leaflet-map]').forEach((mapElement) => {
    mapElement._scheduleLeaflet?.map?.remove();
    delete mapElement._scheduleLeaflet;
    delete mapElement.dataset.leafletReady;
  });
  document.querySelectorAll('[data-admin-leaflet-map]').forEach((mapElement) => {
    mapElement._adminLeaflet?.map?.remove();
    delete mapElement._adminLeaflet;
    delete mapElement.dataset.adminLeafletReady;
  });
  document.querySelectorAll('[data-login-carousel]').forEach((carousel) => {
    window.clearInterval(carousel._carouselTimer);
    delete carousel.dataset.carouselReady;
  });
  document.querySelectorAll('[data-report-dashboard]').forEach((container) => delete container.dataset.reportReady);
  document.querySelectorAll('[data-notification-center]').forEach((container) => delete container.dataset.notificationReady);
  document.querySelectorAll('[data-notification-drawer]').forEach((drawer) => delete drawer.dataset.drawerReady);
  document.querySelectorAll('[data-inventory-workspace]').forEach((workspace) => delete workspace.dataset.inventoryReady);
  closeMobileNavDrawer({ immediate: true });
  closeNotificationDrawer();
  closeModal(document.querySelector('.modal-shell.is-open'));
  hideTooltip();
});

document.addEventListener('click', (event) => {
  const newDonation = event.target.closest('[data-new-donation]');
  if (newDonation) {
    event.preventDefault();
    closeMobileNavDrawer();
    openNewDonation(newDonation);
    return;
  }

  const mobileDrawerClose = event.target.closest('[data-mobile-drawer-close]');
  if (mobileDrawerClose || event.target.matches('[data-mobile-drawer-backdrop]')) {
    event.preventDefault();
    closeMobileNavDrawer();
    return;
  }

  const mobileMenuOpen = event.target.closest('[data-mobile-menu-open]');
  if (mobileMenuOpen) {
    event.preventDefault();
    openMobileNavDrawer();
    return;
  }

  const mobileRailTab = event.target.closest('[data-mobile-rail-tab]');
  if (mobileRailTab && mobileRailTab.dataset.panelUrl) {
    event.preventDefault();
    loadPortalPanel(mobileRailTab);
    openMobileNavDrawer();
    return;
  }

  const mobileDrawerTab = event.target.closest('[data-mobile-drawer-tab]');
  if (mobileDrawerTab && mobileDrawerTab.dataset.panelUrl) {
    event.preventDefault();
    loadPortalPanel(mobileDrawerTab);
    closeMobileNavDrawer();
    return;
  }

  const inventoryFilterToggle = event.target.closest('[data-inventory-filter-toggle]');
  if (inventoryFilterToggle) {
    const menu = inventoryFilterToggle.closest('.inventory-filter-wrap')?.querySelector('[data-inventory-filter-menu]');
    if (menu) {
      const nextOpen = menu.hidden;
      closeInventoryFilters(nextOpen ? menu : null);
      menu.hidden = !nextOpen;
      inventoryFilterToggle.setAttribute('aria-expanded', String(nextOpen));
      refreshIcons();
    }
    return;
  }

  const inventoryReset = event.target.closest('[data-inventory-filter-reset]');
  if (inventoryReset) {
    const workspace = inventoryReset.closest('[data-inventory-workspace]');
    if (workspace) {
      workspace.querySelector('[data-inventory-search]').value = '';
      workspace.querySelector('[data-inventory-blood-filter]').value = 'all';
      workspace.querySelector('[data-inventory-status-filter]').value = 'all';
      workspace.querySelector('[data-inventory-sort-filter]').value = 'date-desc';
      applyInventoryFilters(workspace);
    }
    return;
  }

  if (!event.target.closest('.inventory-filter-wrap')) {
    closeInventoryFilters();
  }

  const scheduleReset = event.target.closest('[data-schedule-reset]');
  if (scheduleReset) {
    const confirmed = scheduleReset.closest('.schedule-confirmed');
    const wizard = document.querySelector('[data-schedule-wizard]');
    if (confirmed) {
      confirmed.hidden = true;
    }
    if (wizard) {
      wizard.hidden = false;
      showScheduleStep(wizard, 1);
    }
    return;
  }

  const scheduleNext = event.target.closest('[data-schedule-next]');
  if (scheduleNext) {
    const wizard = scheduleNext.closest('[data-schedule-wizard]');
    showScheduleStep(wizard, Number(wizard?.dataset.scheduleCurrentStep || 1) + 1);
    return;
  }

  const scheduleBack = event.target.closest('[data-schedule-back]');
  if (scheduleBack) {
    const wizard = scheduleBack.closest('[data-schedule-wizard]');
    showScheduleStep(wizard, Number(wizard?.dataset.scheduleCurrentStep || 1) - 1);
    return;
  }

  const scheduleEdit = event.target.closest('[data-schedule-edit]');
  if (scheduleEdit) {
    showScheduleStep(scheduleEdit.closest('[data-schedule-wizard]'), 2);
    return;
  }

  const scheduleService = event.target.closest('[data-schedule-service]');
  if (scheduleService) {
    const wizard = scheduleService.closest('[data-schedule-wizard]');
    setScheduleService(wizard, scheduleService.dataset.scheduleService);
    return;
  }

  const scheduleTime = event.target.closest('[data-schedule-time]');
  if (scheduleTime) {
    const wizard = scheduleTime.closest('[data-schedule-wizard]');
    wizard.querySelectorAll('[data-schedule-time]').forEach((button) => {
      button.classList.toggle('is-selected', button === scheduleTime);
    });
    wizard.querySelector('[data-schedule-time-input]').value = scheduleTime.dataset.scheduleTime;
    updateScheduleReview(wizard);
    return;
  }

  const scheduleCenterChoice = event.target.closest('[data-schedule-center-choice]');
  if (scheduleCenterChoice) {
    selectScheduleCenter(scheduleCenterChoice.closest('[data-schedule-wizard]'), {
      id: scheduleCenterChoice.dataset.centerId,
      name: scheduleCenterChoice.dataset.centerName,
      address: scheduleCenterChoice.dataset.centerAddress,
      tag: scheduleCenterChoice.dataset.centerTag,
      pin: scheduleCenterChoice.dataset.centerPin,
    });
    return;
  }

  const scheduleMapZoom = event.target.closest('[data-schedule-map-zoom]');
  if (scheduleMapZoom) {
    const map = scheduleMapZoom.closest('.static-map');
    const tiles = map?.querySelector('[data-schedule-map-tiles]');
    if (tiles) {
      const current = Number(tiles.dataset.zoomScale || 1.05);
      const next = scheduleMapZoom.dataset.scheduleMapZoom === 'in'
        ? Math.min(1.45, current + 0.12)
        : Math.max(0.82, current - 0.12);
      tiles.dataset.zoomScale = String(next);
      tiles.style.transform = `translate(-50%, -50%) scale(${next})`;
    }
    return;
  }

  const securityReset = event.target.closest('[data-security-reset]');
  if (securityReset) {
    const panel = document.querySelector(securityReset.dataset.securityReset);
    if (panel) {
      panel.hidden = false;
      panel.querySelector('input')?.focus({ preventScroll: true });
    }
    return;
  }

  const campaignCreate = event.target.closest('[data-campaign-create]');
  if (campaignCreate) {
    resetCampaignForm();
  }

  const campaignOpen = event.target.closest('[data-campaign-open]');
  if (campaignOpen) {
    openModal(document.querySelector(campaignOpen.dataset.campaignOpen));
    return;
  }

  const campaignEdit = event.target.closest('[data-campaign-edit]');
  if (campaignEdit) {
    fillCampaignForm({
      title: campaignEdit.dataset.campaignTitle,
      status: campaignEdit.dataset.campaignStatus,
      dateRange: campaignEdit.dataset.campaignDateRange,
      locations: campaignEdit.dataset.campaignLocations,
      imageUrl: campaignEdit.dataset.campaignImageUrl,
      description: campaignEdit.dataset.campaignDescription,
    });
    closeModal(campaignEdit.closest('[data-modal]'));
    openModal(document.querySelector('#campaign-modal'));
    return;
  }

  const modalOpen = event.target.closest('[data-modal-open]');
  if (modalOpen) {
    openModal(document.querySelector(modalOpen.dataset.modalOpen));
    return;
  }

  const modalClose = event.target.closest('[data-modal-close]');
  if (modalClose) {
    closeModal(modalClose.closest('[data-modal]'));
    return;
  }

  if (event.target.matches('[data-modal-backdrop]')) {
    closeModal(event.target.closest('[data-modal]'));
    return;
  }

  const reportFilter = event.target.closest('[data-report-filter]');
  if (reportFilter) {
    applyReportFilter(reportFilter.dataset.reportFilter);
    return;
  }

  const drawerOpen = event.target.closest('[data-notification-drawer-open]');
  if (drawerOpen) {
    openNotificationDrawer();
    return;
  }

  const drawerClose = event.target.closest('[data-notification-drawer-close]');
  if (drawerClose) {
    closeNotificationDrawer();
    return;
  }

  const notificationFilter = event.target.closest('[data-notification-filter]');
  if (notificationFilter) {
    applyNotificationFilter(
      notificationFilter.dataset.notificationFilter,
      notificationFilter.closest('[data-notification-center]')
    );
    return;
  }

  const notificationItem = event.target.closest('[data-notification-item]');
  if (notificationItem) {
    const container = notificationItem.closest('[data-notification-center]');
    container?.querySelectorAll('[data-notification-item]').forEach((item) => item.classList.remove('is-selected'));
    notificationItem.classList.add('is-selected');
    showNotificationError(container);
    updateNotificationDetail(notificationItem);
    markNotificationItemRead(notificationItem);
    return;
  }

  const markRead = event.target.closest('[data-notification-mark-read]');
  if (markRead) {
    markVisibleNotificationsRead(markRead.closest('[data-notification-center]'));
    return;
  }

  const portalTab = event.target.closest('[data-portal-tab]');
  if (portalTab && portalTab.dataset.panelUrl) {
    event.preventDefault();
    loadPortalPanel(portalTab);
    return;
  }

  const toggle = event.target.closest('[data-toggle]');
  if (toggle) {
    const target = document.querySelector(toggle.dataset.toggle);
    if (target) {
      target.hidden = !target.hidden;
      refreshIcons();
    }
  }

  const tab = event.target.closest('[data-tab-target]');
  if (tab) {
    const group = tab.dataset.tabGroup;
    document.querySelectorAll(`[data-tab-group="${group}"]`).forEach((button) => {
      button.classList.toggle('is-active', button === tab);
    });
    document.querySelectorAll(`[data-tab-panel="${group}"]`).forEach((panel) => {
      panel.hidden = panel.id !== tab.dataset.tabTarget;
    });
    refreshIcons();
  }
});

document.addEventListener('change', (event) => {
  const inventoryControl = event.target.closest('[data-inventory-blood-filter], [data-inventory-status-filter], [data-inventory-sort-filter]');
  if (inventoryControl) {
    applyInventoryFilters(inventoryControl.closest('[data-inventory-workspace]'));
    return;
  }

  const centerSelect = event.target.closest('[data-schedule-center-select]');
  if (centerSelect) {
    const wizard = centerSelect.closest('[data-schedule-wizard]');
    const option = centerSelect.selectedOptions[0];
    selectScheduleCenter(wizard, {
      id: option?.dataset.id || 'CTR-SR-LAGUNA',
      name: option?.value || centerSelect.value,
      address: option?.dataset.address || '',
      type: option?.dataset.type || '',
      tag: option?.dataset.tag || '',
      pin: option?.dataset.pin || '',
      lat: option?.dataset.lat || 14.31554,
      lng: option?.dataset.lng || 121.11104,
    });
    return;
  }

  const dateInput = event.target.closest('[data-schedule-date-input]');
  if (dateInput) {
    updateScheduleReview(dateInput.closest('[data-schedule-wizard]'));
  }
});

document.addEventListener('input', (event) => {
  const inventorySearch = event.target.closest('[data-inventory-search]');
  if (inventorySearch) {
    applyInventoryFilters(inventorySearch.closest('[data-inventory-workspace]'));
  }
});

window.addEventListener('hashchange', () => {
  const panelName = currentPortalTab();
  const tab = panelName ? document.querySelector(`[data-portal-tab="${CSS.escape(panelName)}"]`) : null;
  if (tab) {
    loadPortalPanel(tab, { push: false });
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeMobileNavDrawer();
    closeModal(document.querySelector('.modal-shell.is-open'));
    closeNotificationDrawer();
  }

  if (event.key === 'Enter' || event.key === ' ') {
    const notificationItem = event.target.closest?.('[data-notification-item]');
    const campaignCard = event.target.closest?.('[data-campaign-open]');

    if (notificationItem || campaignCard) {
      event.preventDefault();
      (notificationItem || campaignCard).click();
    }
  }
});

document.addEventListener('pointerover', (event) => {
  const target = event.target.closest('[data-tooltip]');
  if (target) {
    showTooltip(target, event);
  }
});

document.addEventListener('pointermove', (event) => {
  if (event.target.closest('[data-tooltip]')) {
    moveTooltip(event);
  }
});

document.addEventListener('pointerout', (event) => {
  if (event.target.closest('[data-tooltip]')) {
    hideTooltip();
  }
});

document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-spin]');
  if (button) {
    button.animate([{ transform: 'rotate(0deg)' }, { transform: 'rotate(360deg)' }], {
      duration: 420,
      easing: 'ease-out',
    });
  }
});
