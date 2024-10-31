import { createBlock } from '@wordpress/blocks';

const transforms = {
  from: [
    {
      type: 'shortcode',
      tag: 'salesbox-crm-form',
      attributes: {
        id: {
          type: 'integer',
          shortcode: ( { named: { id } } ) => {
            return parseInt( id );
          },
        },
        title: {
          type: 'string',
          shortcode: ( { named: { title } } ) => {
            return title;
          },
        },
      },
    },
  ],
  to: [
    {
      type: 'block',
      blocks: [ 'core/shortcode' ],
      transform: ( attributes ) => {
        return createBlock(
          'core/shortcode',
          {
            text: `[salesbox-crm-form id="${ attributes.id }" title="${ attributes.title }"]`,
          }
        );
      },
    },
  ],
};

export default transforms;
