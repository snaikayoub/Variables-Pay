import { startStimulusApp } from '@symfony/stimulus-bundle';

import CheckAllController from './controllers/check_all_controller.js';
import ConfirmController from './controllers/confirm_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

app.register('check-all', CheckAllController);
app.register('confirm', ConfirmController);
