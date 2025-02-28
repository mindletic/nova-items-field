<template>
    <default-field :field="field" :full-width-content="field.fullWidth" :show-help-text="showHelpText">
        <template slot="field" class="nova-items-field">
            <div class="nova-items-field-input-wrapper flex border border-40 p-4"
                 v-if="field.listFirst == false && ! maxReached">
                <input
                    v-model="newItem"
                    :type="field.inputType"
                    :placeholder="field.placeholder"
                    autocomplete="new-password"
                    @keydown.enter.prevent="addItem"
                    class="flex-1 form-control form-input form-input-bordered"
                />
                <a
                    @click="addItem"
                    v-html="field.createButtonValue"
                    v-if="field.hideCreateButton == false"
                    class="btn btn-default btn-primary ml-3 cursor-pointer font-sans"
                />
            </div>
            <ul ref="novaitemslist" :style="maxHeight" v-if="items.length"
                class="nova-items-field-input-items list-reset border border-40 py-2">
                <draggable v-model="items"
                           :options="{ disabled: field.draggable == false, handle: '.sortable-handle' }">
                    <li
                        v-for="(item, index) in items"
                        :key="field.attribute + '.' + index"
                        class="px-4 py-2"
                    >

                        <div class="nova-items-field-input-wrapper-item flex py-1">
                        <span v-if="field.draggable" class="sortable-handle py-2 pl-0 pr-4 text-80 cursor-move">
                            |||
                        </span>
                            <input
                                :value="item"
                                :type="field.inputType"
                                v-on:keyup="updateItem(index, $event)"
                                :name="field.name + '['+ index +']'"
                                autocomplete="new-password"
                                :class="{'border-danger': hasErrors(field.attribute + '.' + index)}"
                                class="flex-1 form-control form-input form-input-bordered"
                            >
                            <span
                                @click="removeItem(index)"
                                style="font-size: 32px;"
                                class="ml-4 mr-2 font-thin cursor-pointer hover:font-normal"
                                v-html="field.deleteButtonValue"
                            />
                        </div>
                        <div v-if="hasErrors(field.attribute + '.' + index)"
                             class="help-text error-text -mt-2 text-danger py-2">
                            <p v-html="arrayErrors[field.attribute + '.' + index][0]"/>
                        </div>
                    </li>
                </draggable>
            </ul>
            <div class="nova-items-field-input-wrapper flex border border-40 p-4"
                 v-if="field.listFirst && ! maxReached">
                <input
                    v-model="newItem"
                    :type="field.inputType"
                    :placeholder="field.placeholder"
                    class="flex-1 form-control form-input form-input-bordered"
                    @keypress.enter.prevent="addItem"
                    @paste="handlePaste"
                    @blur="addItem"
                />
                <a
                    @click="addItem"
                    v-html="field.createButtonValue"
                    v-if="field.hideCreateButton == false"
                    class="btn btn-default btn-primary ml-3 cursor-pointer"
                />
            </div>
            <div v-if="globalError"
                 class="help-text error-text -mt-2 text-danger py-2">
                <p v-html="globalError"/>
            </div>
        </template>
    </default-field>
</template>

<style scoped>
.sortable-chosen {
    border: 1px solid var(--50);
    background-color: var(--20);
    box-shadow: 2px 2px 2px var(--40);
    margin-left: -5px;
}

.sortable-handle {
    -o-user-select: none;
    -moz-user-select: none;
    -webkit-user-select: none;
    user-select: none;
    -webkit-transform: rotate(90deg);
    -moz-transform: rotate(90deg);
    -o-transform: rotate(90deg);
    -ms-transform: rotate(90deg);
    transform: rotate(90deg);
    position: relative;
    left: -5px;
    top: 5px;
}
</style>

<script>
import draggable from 'vuedraggable'
import {FormField, HandlesValidationErrors} from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    components: {draggable},

    data: function () {
        return {
            value: '',
            items: [],
            newItem: '',
            arrayErrors: [],
            globalError: null,
        }
    },

    methods: {


        setInitialValue() {
            this.value = this.field.value || [];
            this.items = this.field.value || [];
        },


        fill(formData) {
            formData.append(this.field.attribute, this.value || [])
        },

        addItem() {
            const item = this.prepareItem(this.newItem)

            if (item && !this.maxReached) {
                this.items.push(item)
                this.newItem = ''

                this.$nextTick(() => {
                    if (this.field.maxHeight) {
                        this.$refs.novaitemslist.scrollTop = this.$refs.novaitemslist.scrollHeight;
                    }
                })
            }
        },

        handlePaste(event) {
            if (!this.field.allowPastingMultiple) {
                return;
            }

            event.preventDefault();
            const value = event.clipboardData.getData('text')
            const regex = new RegExp("[\n" + this.field.trimCharacters.join('') + "]+", "g");
            const items = value.split(regex);

            items.forEach((item) => {
                item = this.prepareItem(item)

                if (item && !this.maxReached) {
                    this.items.push(item)
                }
            })

            this.$nextTick(() => {
                if (this.field.maxHeight) {
                    this.$refs.novaitemslist.scrollTop = this.$refs.novaitemslist.scrollHeight;
                }
            })
        },

        prepareItem(item) {
            item = item.trim()

            return item
        },

        updateItem(index, event) {
            this.$set(this.items, index, event.target.value)
        },

        removeItem(index) {
            this.items.splice(index, 1)
        },

        hasErrors(key) {
            return this.arrayErrors.hasOwnProperty(key);
        }
    },
    computed: {
        maxHeight() {
            if (this.field.maxHeight == false) {
                return '';
            }

            return `max-height: ${this.field.maxHeight}px; overflow: auto;`;
        },
        maxReached() {
            return this.field.max !== false && this.items.length + 1 > this.field.max;
        }
    },
    watch: {
        'items': {
            handler: function (items) {
                this.value = JSON.stringify(items);
            },
            deep: true
        },
        'errors': {
            handler: function (errors) {
                if (errors.errors.hasOwnProperty(this.field.attribute)) {
                    const allErrors = JSON.parse(errors.errors[this.field.attribute][0])

                    this.arrayErrors = allErrors
                    this.globalError = allErrors.hasOwnProperty(this.field.attribute)
                        ? allErrors[this.field.attribute][0]
                        : null;
                }
            },
            deep: true
        }
    }
}
</script>
