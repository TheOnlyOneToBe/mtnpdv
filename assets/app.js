import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import PdvFormController from './controllers/pdv-form-controller';
import PdvListController from './controllers/pdv-list-controller';
import PdvSearchController from './controllers/pdv-search-controller';
import PdvMapController from './controllers/pdv-map-controller';
import ChartsController from './controllers/charts-controller';
import ToastController from './controllers/toast-controller';
import FlashToastController from './controllers/flash-toast-controller';
import DarkModeController from './controllers/dark-mode-controller';
import LazyLoadingController from './controllers/lazy-loading-controller';
import SidebarController from './controllers/sidebar-controller';
import SessionLockController from './controllers/session-lock-controller';
import NotificationsController from './controllers/notifications-controller';

window.Turbo = Turbo;

const application = Application.start();
application.register('pdv-form', PdvFormController);
application.register('pdv-list', PdvListController);
application.register('pdv-search', PdvSearchController);
application.register('pdv-map', PdvMapController);
application.register('charts', ChartsController);
application.register('toast', ToastController);
application.register('flash-toast', FlashToastController);
application.register('dark-mode', DarkModeController);
application.register('lazy-loading', LazyLoadingController);
application.register('sidebar', SidebarController);
application.register('session-lock', SessionLockController);
application.register('notifications', NotificationsController);

// Expose Stimulus application globally for flash-toast controller
window.Stimulus = {
  Application: Application,
  application: application
};
// For compatibility with controllers expecting .current
window.Stimulus.Application.current = application;
