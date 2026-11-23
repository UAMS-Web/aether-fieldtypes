// Control Panel entry point. Components, fieldtypes and Inertia pages register inside the
// booting callback so they run once Statamic has initialized.
import AudioFieldtype from './components/fieldtypes/AudioFieldtype.vue'
import CheckboxesFieldtype from './components/fieldtypes/CheckboxesFieldtype.vue'
import CssSelectorFieldtype from './components/fieldtypes/CssSelectorFieldtype.vue'
import CurrencyFieldtype from './components/fieldtypes/CurrencyFieldtype.vue'
import CustomInput from './components/fieldtypes/partials/CustomInput.vue'
import CustomInputText from './components/fieldtypes/partials/CustomInputText.vue'
import DateFieldtype from './components/fieldtypes/DateFieldtype.vue'
import EmailFieldtype from './components/fieldtypes/EmailFieldtype.vue'
import EntriesFieldtype from './components/fieldtypes/EntriesFieldtype.vue'
import FloatvalFieldtype from './components/fieldtypes/FloatvalFieldtype.vue'
import IdentifierFieldtype from './components/fieldtypes/IdentifierFieldtype.vue'
import IntegerFieldtype from './components/fieldtypes/IntegerFieldtype.vue'
import MonthFieldtype from './components/fieldtypes/MonthFieldtype.vue'
import PasswordFieldtype from './components/fieldtypes/PasswordFieldtype.vue'
import PhoneFieldtype from './components/fieldtypes/PhoneFieldtype.vue'
import RadioFieldtype from './components/fieldtypes/RadioFieldtype.vue'
import SubsectionFieldtype from './components/fieldtypes/SubsectionFieldtype.vue'
import TextFieldtype from './components/fieldtypes/TextFieldtype.vue'
import TrueFalseFieldtype from './components/fieldtypes/TrueFalseFieldtype.vue'
import UrlFieldtype from './components/fieldtypes/UrlFieldtype.vue'
import VideoFieldtype from './components/fieldtypes/VideoFieldtype.vue'
import WeekFieldtype from './components/fieldtypes/WeekFieldtype.vue'
import XpathFieldtype from './components/fieldtypes/XpathFieldtype.vue'
import ZipCodeFieldtype from './components/fieldtypes/ZipCodeFieldtype.vue'

Statamic.booting(() => {
    // The shared input components every input fieldtype below renders through.
    Statamic.$components.register('custom-input', CustomInput)
    Statamic.$components.register('custom-text-input', CustomInputText)

    Statamic.$components.register('css_selector-fieldtype', CssSelectorFieldtype)
    Statamic.$components.register('custom_audio-fieldtype', AudioFieldtype)
    Statamic.$components.register('custom_checkboxes-fieldtype', CheckboxesFieldtype)
    Statamic.$components.register('currency-fieldtype', CurrencyFieldtype)
    Statamic.$components.register('custom_date-fieldtype', DateFieldtype)
    Statamic.$components.register('custom_floatval-fieldtype', FloatvalFieldtype)
    Statamic.$components.register('custom_integer-fieldtype', IntegerFieldtype)
    Statamic.$components.register('custom_radio-fieldtype', RadioFieldtype)
    Statamic.$components.register('custom_text-fieldtype', TextFieldtype)
    Statamic.$components.register('custom_video-fieldtype', VideoFieldtype)
    Statamic.$components.register('email-fieldtype', EmailFieldtype)
    Statamic.$components.register('entries-fieldtype', EntriesFieldtype)
    Statamic.$components.register('identifier-fieldtype', IdentifierFieldtype)
    Statamic.$components.register('month-fieldtype', MonthFieldtype)
    Statamic.$components.register('password-fieldtype', PasswordFieldtype)
    Statamic.$components.register('phone_input-fieldtype', PhoneFieldtype)
    Statamic.$components.register('subsection-fieldtype', SubsectionFieldtype)
    Statamic.$components.register('true_false-fieldtype', TrueFalseFieldtype)
    Statamic.$components.register('url-fieldtype', UrlFieldtype)
    Statamic.$components.register('week-fieldtype', WeekFieldtype)
    Statamic.$components.register('xpath-fieldtype', XpathFieldtype)
    Statamic.$components.register('zip_code-fieldtype', ZipCodeFieldtype)
})
