/* Collection selection is independent of the optional contribution metabox. */
(function(wp){
  'use strict';
  const el=wp.element.createElement, __=wp.i18n.__, domain='builder-pdf-presenter';
  const {InspectorControls,MediaUpload,MediaUploadCheck,useBlockProps}=wp.blockEditor;
  const {PanelBody,SelectControl,TextControl,Button,Placeholder,FontSizePicker,RangeControl}=wp.components;
  const inherited={label:__('Inherit',domain),value:''};
  function CollectionEditor({entries,onChange,config}){
    const ref=wp.element.useRef();
    wp.element.useEffect(()=>{
      let active=true;
      Promise.all(entries.map(async entry=>{
        try{const media=await wp.apiFetch({path:'/wp/v2/media/'+entry.pdf});const cover=entry.cover?await wp.apiFetch({path:'/wp/v2/media/'+entry.cover}):null;return {...entry,available:true,suggestion:media.title.rendered.replace(/<[^>]*>/g,''),preview:cover?(cover.media_details?.sizes?.medium?.source_url||cover.source_url||''):(media.media_details?.sizes?.medium?.source_url||''),date:media.date,filename:decodeURIComponent(media.source_url.split('/').pop()),size:media.media_details?.filesize??null};}
        catch{return {...entry,available:false};}
      })).then(rows=>{if(active)new window.BPPManager(ref.current,{...config,entries:rows},onChange);});
      return()=>{active=false;};
    },[JSON.stringify(config.defaults),config.postId,config.limit]);
    return el('div',{ref,className:'bpp-block-manager'});
  }
  // Original PDF Presenter document icon, shared with the native builder elements.
  const pdfIcon=el('svg',{viewBox:'0 0 24 24',width:24,height:24,'aria-hidden':true},
    el('path',{d:'M3 5h10l4 4v13H3zM13 5v5h4',fill:'none',stroke:'currentColor',strokeWidth:1.5,strokeLinecap:'round',strokeLinejoin:'round'}),
    el('path',{d:'M5 14h10v5H5z',fill:'currentColor'}),
    el('path',{d:'M6 18v-3h1a.75.75 0 0 1 0 1.5H6m3 1.5v-3h.6c1.7 0 1.7 3 0 3H9m3 0v-3h1.5M12 16.5h1.2',fill:'none',stroke:'white',strokeWidth:.5}));
  function Example(){return el('div',{className:'bpp-inserter-example'},[1,2].map(n=>el('article',{key:n,className:'bpp-card'},el('div',{className:'bpp-example-art'},'PDF'),el('div',{className:'bpp-info'},el('h3',{className:'bpp-title'},__('Documents and publications',domain)),el('p',{className:'bpp-meta'},'PDF · 2 MB'),el('span',{className:'bpp-button'},__('Read',domain))))));}
  function StyleControls({attributes:a,setAttributes:set}){
    const styles=a.cardStyles||{},palette=wp.blockEditor.useSettings('color.palette')[0]||[];
    const update=(key,value)=>{const next={...styles};if(value===undefined||value==='')delete next[key];else next[key]=String(value);set({cardStyles:next});};
    const size=(key,label)=>el('div',{key},el('p',null,label),el(FontSizePicker,{value:parseFloat(styles[key])||undefined,onChange:v=>update(key,v===undefined?'':v+'px'),withSlider:true,units:['px'],fontSizes:[{name:__('Small',domain),slug:'small',size:14},{name:__('Medium',domain),slug:'medium',size:18},{name:__('Large',domain),slug:'large',size:24}]}));
    const color=(key,label)=>el('div',{key},el('p',null,label),el(wp.blockEditor.ColorPalette,{colors:palette,value:styles[key],onChange:v=>update(key,v)}));
    return el(InspectorControls,{group:'styles'},
      el(PanelBody,{title:__('Title',domain),initialOpen:false},size('titleSize',__('Font size',domain)),el(SelectControl,{label:__('Font weight',domain),value:styles.titleWeight||'',options:[inherited,...['400','500','600','700'].map(value=>({label:value,value}))],onChange:v=>update('titleWeight',v)}),el(RangeControl,{label:__('Line height',domain),value:Number(styles.titleLineHeight)||1.35,min:1,max:2,step:.05,onChange:v=>update('titleLineHeight',v)}),color('titleColor',__('Text color',domain))),
      el(PanelBody,{title:__('File details',domain),initialOpen:false},size('metaSize',__('Font size',domain)),color('metaColor',__('Text color',domain))),
      el(PanelBody,{title:__('Buttons',domain),initialOpen:false},size('buttonSize',__('Font size',domain)),color('buttonColor',__('Reading button color',domain)),color('buttonBackground',__('Reading button background',domain)),color('downloadColor',__('Download button color',domain)),color('downloadBackground',__('Download button background',domain))),
      el(PanelBody,{title:__('Document card',domain),initialOpen:false},color('cardBackground',__('Card background',domain)),color('borderColor',__('Card border',domain)),el(RangeControl,{label:__('Card radius',domain),value:parseFloat(styles.cardRadius)||12,min:0,max:40,onChange:v=>update('cardRadius',v+'px')}),el(RangeControl,{label:__('Card padding',domain),value:parseFloat(styles.cardPadding)||20,min:0,max:60,onChange:v=>update('cardPadding',v+'px')}),el(Button,{variant:'secondary',onClick:()=>set({cardStyles:{}})},__('Reset card styles',domain)))
    );
  }
  wp.blocks.registerBlockType('builder-pdf-presenter/document',{
    icon:pdfIcon,
    description:__('Present a PDF or a document collection with preview covers, reading and download buttons.',domain),
    edit:function(props){
      const a=props.attributes,set=props.setAttributes;
      if(a.isExample)return el(Example);
      const [view,setView]=wp.element.useState('edit'),[preview,setPreview]=wp.element.useState(null),[error,setError]=wp.element.useState('');
      const [context,setContext]=wp.element.useState(window.bppEditor||{});
      const initialized=wp.element.useRef(false),hadDocuments=wp.element.useRef(Boolean(a.attachmentId||(a.documents||[]).length));
      const currentId=wp.data.useSelect(select=>select('core/editor')?.getCurrentPostId()||window.bppEditor?.postId||0,[]);
      const payload=JSON.stringify(a);
      wp.element.useEffect(()=>{
        let alive=true;const controller=new AbortController();
        const timer=setTimeout(async()=>{try{
          const base=window.bppEditor||{},body=new URLSearchParams({action:'bpp_block_preview',_ajax_nonce:base.blockNonce,post_id:String(currentId),attributes:payload});
          const response=await fetch(base.ajax,{method:'POST',credentials:'same-origin',body,signal:controller.signal});const result=await response.json();
          if(!result.success)throw new Error(result.data?.message||__('Preview could not be loaded.',domain));
          if(alive){setPreview(result.data);setContext(result.data.context);setError('');if(!initialized.current){initialized.current=true;if(result.data.reader)setView('preview');}}
        }catch(e){if(alive&&e.name!=='AbortError')setError(e.message||__('Preview could not be loaded.',domain));}},250);
        return()=>{alive=false;clearTimeout(timer);controller.abort();};
      },[payload,currentId]);
      const selectDocument=media=>{set({attachmentId:media.id,source:'post'});setView('preview');};
      const changeDocuments=documents=>{set({documents});if(!hadDocuments.current&&documents.length)setView('preview');hadDocuments.current=Boolean(documents.length);};
      const select=(key,label,choices)=>el(SelectControl,{label,value:a[key]||'',options:[inherited].concat(choices),onChange:value=>set({[key]:value})});
      const toggle=(key,label)=>select(key,label,[{label:__('Show / enable',domain),value:'1'},{label:__('Hide / disable',domain),value:'0'}]);
      const source=el(SelectControl,{label:__('Document source',domain),value:a.source,options:[{label:__('Documents assigned to this contribution',domain),value:'post'},{label:__('Own PDF collection',domain),value:'custom'},{label:__('Query document contributions',domain),value:'posts'}],onChange:value=>set({source:value,attachmentId:0})});
      const settings=el(InspectorControls,null,el(PanelBody,{title:__('Document settings',domain)},source,
        el(TextControl,{label:__('Source post ID (optional)',domain),type:'number',value:a.postId||'',onChange:value=>set({postId:Math.max(0,parseInt(value,10)||0)})}),
        a.source==='posts'&&el(SelectControl,{label:__('Content types',domain),value:a.post_type,options:Object.entries(context.types||window.bppEditor?.types||{}).map(([value,label])=>({value,label})),onChange:value=>set({post_type:value})}),
        el(TextControl,{label:__('Collection ID',domain),help:__('Use a unique name, such as newsletters. Enter the same name in PDF filters and search.',domain),value:a.collection_id,onChange:v=>set({collection_id:v.toLowerCase().replace(/[^a-z0-9_-]/g,'')})}),
        a.source==='posts'&&select('per_post',__('PDFs per source post',domain),[{label:__('First available PDF',domain),value:'first'},{label:__('All assigned PDFs',domain),value:'all'}]),
        toggle('show_count',__('Show result count',domain)),
        select('orderby',__('Output sorting',domain),Object.entries(context.sorts||window.bppEditor?.sorts||{}).map(([value,label])=>({value,label}))),
        select('order',__('Direction',domain),[{label:__('Ascending',domain),value:'ASC'},{label:__('Descending',domain),value:'DESC'}]),
        el(TextControl,{label:__('PDFs per page (0: use collection default)',domain),type:'number',value:a.page_size,onChange:v=>set({page_size:Math.max(0,Math.min(500,parseInt(v,10)||0))})}),
        el('fieldset',null,el('legend',null,__('Search fields',domain)),...Object.entries({title:__('Display title',domain),post_title:__('Source post title',domain),media_title:__('Media Library title',domain),filename:__('File name',domain)}).map(([key,label])=>el(wp.components.CheckboxControl,{key,label,checked:(a.search_fields||'').split(',').includes(key),onChange:v=>{const fields=new Set((a.search_fields||'').split(',').filter(Boolean));if(v)fields.add(key);else fields.delete(key);set({search_fields:[...fields].join(',')});}}))),
        select('design',__('Card design',domain),[{label:__('Cover card',domain),value:'cover'},{label:__('Compact card',domain),value:'compact'},{label:__('Document list',domain),value:'list'}]),
        select('columns',__('Collection columns',domain),[1,2,3,4].map(n=>({label:String(n),value:String(n)}))),
        select('mode',__('Open document',domain),[{label:__('Reader in a modal',domain),value:'modal'},{label:__('Reader in a new tab',domain),value:'tab'},{label:__('PDF directly in a new tab',domain),value:'file'}]),
        select('ratio',__('Preview frame',domain),[{label:__('Portrait (A4)',domain),value:'a4'},{label:__('Portrait (3:4)',domain),value:'portrait'},{label:__('Square',domain),value:'square'},{label:__('Landscape (4:3)',domain),value:'landscape'},{label:__('Original proportions',domain),value:'auto'}]),
        context.watermarkExceptions&&toggle('reader_watermark',__('Use reader watermark',domain)),toggle('show_title',__('Show title',domain)),toggle('show_read',__('Show reading button',domain)),toggle('show_download',__('Show download button',domain)),toggle('cover_link',__('Make preview clickable',domain)),toggle('show_size',__('Show file size',domain)),
        el(TextControl,{label:__('Reading button label',domain),value:a.read_label,onChange:value=>set({read_label:value})}),el(TextControl,{label:__('Download button label',domain),value:a.download_label,onChange:value=>set({download_label:value})})
      ));
      return el(wp.element.Fragment,null,settings,el(StyleControls,{attributes:a,setAttributes:set}),el('div',useBlockProps({className:'bpp-block-editor'}),
        el('div',{className:'bpp-editor-toolbar'},el('div',{className:'bpp-editor-identity'},pdfIcon,el('span',null,'PDF Presenter'),el('span',{className:'bpp-editor-badge'},__('Block',domain))),
        el('div',{className:'bpp-editor-switch',role:'group','aria-label':__('Block view',domain)},
          el(Button,{variant:view==='edit'?'primary':'secondary','aria-pressed':view==='edit',onClick:()=>setView('edit')},__('Edit',domain)),
          el(Button,{variant:view==='preview'?'primary':'secondary','aria-pressed':view==='preview',onClick:()=>setView('preview')},__('Preview',domain)))),
        el('p',{className:'bpp-editor-only'},__('Editor controls only. This toolbar is not displayed on your website.',domain)),
        error&&el('p',{role:'alert'},error),
        view==='preview'?el('div',null,
          preview?el('div',{className:'bpp-editor-preview',onClick:e=>{if(e.target.closest('a'))e.preventDefault();},dangerouslySetInnerHTML:{__html:preview.html}}):el('p',{role:'status'},__('Loading preview…',domain)),
          preview?.reader&&el('p',null,el(Button,{variant:'secondary',href:preview.reader,target:'_blank',rel:'noopener noreferrer'},__('Test reader',domain)),el('span',{className:'bpp-reader-test-help'},__('Opens the first document in a new tab.',domain))),
          preview&&!preview.reader&&el(Button,{variant:'primary',onClick:()=>setView('edit')},__('Add PDFs',domain))) : el('div',null,
        el('p',{className:'bpp-collection-help'},context.collectionHelp||window.bppEditor?.collectionHelp),
        a.source==='posts'?el(Placeholder,{icon:'media-document',label:__('Document archive',domain),instructions:__('Choose the content type, PDF selection and sorting in the block settings.',domain)},source):a.source==='custom'?el(CollectionEditor,{key:'own-'+props.clientId,entries:a.documents,onChange:changeDocuments,config:context}):el(Placeholder,{icon:'media-document',label:__('Current contribution',domain),instructions:__('Select a PDF, or use the document assigned to the current contribution.',domain)},source,
          a.attachmentId>0&&el('p',null,'PDF #'+a.attachmentId),
          el(MediaUploadCheck,null,el(MediaUpload,{allowedTypes:['application/pdf'],value:a.attachmentId,onSelect:selectDocument,render:control=>el(Button,{variant:'secondary',onClick:control.open},__('Select / replace',domain))})),
          el('p',{className:'bpp-media-help'},__('Opens the Media Library to select or upload a PDF.',domain)),
          a.attachmentId>0&&el(Button,{variant:'secondary',onClick:()=>set({attachmentId:0})},__('Use assigned document',domain))
        ))
      ));
    },save:()=>null
  });
  wp.blocks.registerBlockType('builder-pdf-presenter/filters',{
    edit:({attributes:a,setAttributes:set})=>el('div',useBlockProps({className:'bpp-filter-editor'}),
      el('strong',null,__('PDF filters and search',domain)),
      el('p',null,__('Searches document metadata, not PDF contents. Filters apply to the whole collection before pagination.',domain)),
      el(TextControl,{label:__('Target collection ID',domain),value:a.target,help:__('Use a unique name, such as newsletters. Enter the same name in PDF filters and search.',domain),onChange:v=>set({target:v.toLowerCase().replace(/[^a-z0-9_-]/g,'')})}),
      el(SelectControl,{label:__('Layout',domain),value:a.layout,options:[{label:__('Horizontal',domain),value:'horizontal'},{label:__('Vertical',domain),value:'vertical'}],onChange:v=>set({layout:v})}),
      ...[['show_search',__('Search',domain)],['show_sort',__('Sorting',domain)],['show_size_filter',__('File size filter',domain)],['show_reset',__('Show reset link',domain)]].map(([key,label])=>el(wp.components.ToggleControl,{key,label,checked:a[key]!=='0',onChange:v=>set({[key]:v?'1':'0'})}))),save:()=>null
  });
})(window.wp);
