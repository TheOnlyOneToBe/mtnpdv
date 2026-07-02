import { Controller } from 'https://cdn.jsdelivr.net/npm/@hotwired/stimulus@3.3.2/+esm';

export default class extends Controller {
  static targets = ['modal', 'form', 'passwordInput', 'timerDisplay', 'errorMessage'];
  static values = {
    checkInterval: { type: Number, default: 60 },
    timeoutSeconds: { type: Number, default: 1800 },
    unlockUrl: String,
    lockUrl: String,
  };

  connect() {
    this.checkTimer = null;
    this.countdownTimer = null;
    this.isLocked = false;
    this.lastActivityTime = Date.now();
    this.remainingSeconds = this.timeoutSecondsValue;

    this.initializeActivityTracking();
    this.startInactivityCheck();
  }

  disconnect() {
    if (this.checkTimer) clearInterval(this.checkTimer);
    if (this.countdownTimer) clearInterval(this.countdownTimer);
  }

  initializeActivityTracking() {
    const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
    events.forEach(event => {
      document.addEventListener(event, () => this.recordActivity(), true);
    });
  }

  recordActivity() {
    if (this.isLocked) return;

    this.lastActivityTime = Date.now();
    this.remainingSeconds = this.timeoutSecondsValue;
    this.trackActivityOnServer();
  }

  trackActivityOnServer() {
    fetch(this.checkIntervalValue > 0 ? '/session/activity' : '#', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    }).catch(() => {});
  }

  startInactivityCheck() {
    this.checkTimer = setInterval(() => {
      const elapsedSeconds = Math.floor((Date.now() - this.lastActivityTime) / 1000);
      this.remainingSeconds = Math.max(0, this.timeoutSecondsValue - elapsedSeconds);

      if (this.remainingSeconds <= 0 && !this.isLocked) {
        this.handleInactivityTimeout();
      }

      if (this.isLocked && !this.countdownTimer) {
        this.startCountdownTimer();
      }
    }, 1000);
  }

  startCountdownTimer() {
    this.countdownTimer = setInterval(() => {
      if (this.isLocked && this.hasTimerDisplayTarget) {
        const minutes = Math.floor(this.remainingSeconds / 60);
        const seconds = this.remainingSeconds % 60;
        this.timerDisplayTarget.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (this.remainingSeconds > 0) {
          this.remainingSeconds--;
        }
      }
    }, 1000);
  }

  handleInactivityTimeout() {
    this.savePageState();
    this.lockSessionUI();
    this.sendLockRequest();
  }

  savePageState() {
    const pageState = {
      url: window.location.href,
      scrollPosition: {
        x: window.scrollX,
        y: window.scrollY,
      },
      timestamp: Date.now(),
    };

    localStorage.setItem('pageState', JSON.stringify(pageState));
  }

  restorePageState() {
    const pageState = localStorage.getItem('pageState');
    if (!pageState) return;

    const state = JSON.parse(pageState);
    if (state.url === window.location.href) {
      window.scrollTo(state.scrollPosition.x, state.scrollPosition.y);
    }
  }

  lockSessionUI() {
    this.isLocked = true;
    this.remainingSeconds = this.timeoutSecondsValue;

    if (this.hasModalTarget) {
      this.modalTarget.classList.add('show');
      this.modalTarget.style.display = 'block';
      this.modalTarget.setAttribute('aria-modal', 'true');

      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop fade show';
      backdrop.id = 'sessionLockBackdrop';
      document.body.appendChild(backdrop);
    }

    document.body.classList.add('modal-open');
    this.startCountdownTimer();
  }

  unlockSessionUI() {
    this.isLocked = false;

    if (this.hasModalTarget) {
      this.modalTarget.classList.remove('show');
      this.modalTarget.style.display = 'none';
      this.modalTarget.removeAttribute('aria-modal');
    }

    const backdrop = document.getElementById('sessionLockBackdrop');
    if (backdrop) backdrop.remove();

    document.body.classList.remove('modal-open');

    if (this.countdownTimer) {
      clearInterval(this.countdownTimer);
      this.countdownTimer = null;
    }
  }

  sendLockRequest() {
    const pageState = localStorage.getItem('pageState');
    const state = pageState ? JSON.parse(pageState) : {};

    fetch(this.lockUrlValue, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pageUrl: state.url || window.location.href,
        pageState: JSON.stringify(state),
      }),
    }).catch(() => {});
  }

  async handleUnlock(event) {
    event.preventDefault();

    if (!this.hasPasswordInputTarget) return;

    const password = this.passwordInputTarget.value;
    if (!password) {
      this.showErrorMessage('Veuillez entrer votre mot de passe');
      return;
    }

    try {
      const response = await fetch(this.unlockUrlValue, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password }),
      });

      const data = await response.json();

      if (data.success) {
        this.passwordInputTarget.value = '';
        this.clearErrorMessage();
        this.unlockSessionUI();
        this.restorePageState();
        this.recordActivity();
      } else {
        this.showErrorMessage(data.error || 'Mot de passe incorrect');
      }
    } catch (error) {
      this.showErrorMessage('Erreur lors de la vérification');
    }
  }

  showErrorMessage(message) {
    if (this.hasErrorMessageTarget) {
      this.errorMessageTarget.textContent = message;
      this.errorMessageTarget.classList.add('show');
    }
  }

  clearErrorMessage() {
    if (this.hasErrorMessageTarget) {
      this.errorMessageTarget.textContent = '';
      this.errorMessageTarget.classList.remove('show');
    }
  }
}
