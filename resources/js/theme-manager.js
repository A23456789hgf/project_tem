/**
 * Sovereign Multi-Theme Manager
 * Extracted from React frontend to maintain visual design system state.
 * Presentation only - does not affect backend logic.
 */

const THEMES_LIST = [
  'navy', 'royal_blue', 'ocean', 'emerald', 'forest', 'indigo', 'slate', 'burgundy'
];
const DEFAULT_THEME_ID = 'navy';
const DEFAULT_THEME_MODE = 'dark'; // Matches React's default

function getStoredThemeId() {
  const stored = localStorage.getItem('gov_theme_id');
  if (stored && THEMES_LIST.includes(stored)) {
    return stored;
  }
  return DEFAULT_THEME_ID;
}

function getStoredThemeMode() {
  const stored = localStorage.getItem('gov_theme_mode');
  if (stored && ['light', 'dark', 'system'].includes(stored)) {
    return stored;
  }
  const legacy = localStorage.getItem('gov_theme');
  if (legacy === 'light') return 'light';
  if (legacy === 'dark') return 'dark';
  return DEFAULT_THEME_MODE;
}

export function applyTheme(themeId, mode) {
  const root = document.documentElement;
  
  // Set theme data attributes
  root.setAttribute('data-theme', themeId);
  root.setAttribute('data-mode', mode);

  // Determine dark state
  let isDark = false;
  if (mode === 'dark') {
    isDark = true;
  } else if (mode === 'light') {
    isDark = false;
  } else {
    // system preference
    isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  if (isDark) {
    root.classList.add('dark');
    root.style.colorScheme = 'dark';
  } else {
    root.classList.remove('dark');
    root.style.colorScheme = 'light';
  }

  // Persist settings
  try {
    localStorage.setItem('gov_theme_id', themeId);
    localStorage.setItem('gov_theme_mode', mode);
    localStorage.setItem('gov_theme', isDark ? 'dark' : 'light');
  } catch (e) {
    console.warn('Theme manager: localStorage access denied', e);
  }

  return isDark;
}

// Auto-initialize on script load (runs early)
(function initSovereignTheme() {
  const themeId = getStoredThemeId();
  const themeMode = getStoredThemeMode();
  applyTheme(themeId, themeMode);
  
  // Listen for system changes if set to system mode
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getStoredThemeMode() === 'system') {
      applyTheme(getStoredThemeId(), 'system');
    }
  });
})();

// Expose globally for components to trigger updates
window.SovereignThemeManager = {
  applyTheme,
  getStoredThemeId,
  getStoredThemeMode,
  THEMES_LIST
};
