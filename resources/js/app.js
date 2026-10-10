import './bootstrap';
import './webgis/map';
import './permohonan/location-picker';
import './confirm-dialog';
import './page-loader';
import * as DiagnosisDraft from './diagnosis/draft';

window.SipakarbunDiagnosisDraft = DiagnosisDraft;
window.dispatchEvent(new CustomEvent('sipakarbun:diagnosis-draft-ready'));
