import '@_tw/typography/block-editor-classes';

wp.domReady(() => {
	wp.blocks.registerBlockStyle('core/paragraph', {
		name: 'lead',
		label: 'Lead',
	});
});
