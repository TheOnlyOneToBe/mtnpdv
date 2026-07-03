import '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';
import PdvFormController from './controllers/pdv-form-controller';
import PdvListController from './controllers/pdv-list-controller';
import ToastController from './controllers/toast-controller';
import FlashToastController from './controllers/flash-toast-controller';

// Stimulus setup
const application = Application.start();

application.register('pdv-form', PdvFormController);
application.register('pdv-list', PdvListController);
application.register('toast', ToastController);
application.register('flash-toast', FlashToastController);

// Optional: Enable debug mode in development
// application.debug = true;
