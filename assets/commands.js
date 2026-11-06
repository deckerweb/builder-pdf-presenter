/* Commands navigate only; they never save, migrate or change a document. */
wp.domReady(()=>{if(!wp.commands||!wp.data||!window.bppCommands)return;const store=wp.data.dispatch(wp.commands.store||'core/commands');window.bppCommands.forEach(item=>store.registerCommand({name:item.name,label:item.label,category:'view',callback:({close})=>{close();window.location.assign(item.url);}}));});
