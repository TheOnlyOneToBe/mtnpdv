import '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';
import PdvFormController from './controllers/pdv-form-controller';
import PdvListController from './controllers/pdv-list-controller';

// Stimulus setup
const application = Application.start();

application.register('pdv-form', PdvFormController);
application.register('pdv-list', PdvListController);

// Optional: Enable debug mode in development
// application.debug = true;
