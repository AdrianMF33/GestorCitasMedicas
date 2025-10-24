const { useState, useEffect } = React;

// --- AJUSTE CRÍTICO: CAMBIAR URL A LOCALHOST PARA XAMPP ---
// Si estás usando AWS (IP: 54.242.189.135) usa: 
// const URL_API = "http://54.242.189.135/backend";
// Si estás usando XAMPP localmente (RECOMENDADO):
const URL_API = "http://localhost/PROYECTO_INTEGRACION/backend"; 

/* ---------------- utils ---------------- */
function usarRuta() {
  const [ruta, setRuta] = useState(window.location.hash.replace("#","") || "/");
  useEffect(()=>{
    const onChange = ()=> setRuta(window.location.hash.replace("#","") || "/");
    window.addEventListener("hashchange", onChange);
    return ()=> window.removeEventListener("hashchange", onChange);
  }, []);
  const irA = (p)=> window.location.hash = p;
  return [ruta, irA];
}

async function api(camino, { metodo="GET", cuerpo, token } = {}) {
  const headers = { "Content-Type": "application/json" };
  if (token) headers["Authorization"] = "Bearer " + token;
  
  // Modificación en la llamada a fetch:
  // Si la URL_API es solo el host base, se agrega el camino del endpoint (ej. /login.php)
  const fullUrl = URL_API + (URL_API.endsWith('/') ? '' : '/') + camino.replace(/^\//, '');

  const res = await fetch(fullUrl, { method: metodo, headers, body: cuerpo ? JSON.stringify(cuerpo) : undefined });
  
  if (!res.ok) {
    let msg = "Error";
    try { msg = (await res.json()).detail || msg; } catch(_){}
    throw new Error(msg);
  }
  return res.json();
}

/* ---------------- componentes ---------------- */
function Barra({ autenticado, alSalir, irA }){
  return (
    <div className="navegacion">
      <div className="marca" style={{gap:12}}>
        <img src="../img/hospital_logo.jpg" alt="Hospital Curicó" className="logo-img" />
        <span> <i class="fa-solid fa-screwdriver-wrench"></i> Prototipo - Gestor de citas medicas</span>
      </div>
      <div style={{display:"flex",gap:8}}>
        <button className="boton" onClick={()=>irA("/")}> <i class="fa-solid fa-house"></i> Inicio</button>
        <button className="boton" onClick={()=>window.scrollTo({top:document.body.scrollHeight, behavior:"smooth"})}> <i class="fa-solid fa-phone"></i> Contacto</button>
        {autenticado
          ? <button className="boton primario" onClick={alSalir}>Salir</button>
          : <button className="boton primario" onClick={()=>irA("/ingresar")}> <i class="fa-solid fa-right-to-bracket"></i> Ingresar</button>}
      </div>
    </div>
  );
}

/* ----- Código Div Portada ----- */
function Portada(){
  return (
    <section className="portada">
      <div className="portada-contenido">
        <h1 className="portada-titulo">Comprometidos con tu salud</h1>
        <p className="portada-sub">
          Atención oportuna, humanidad y calidad para la comunidad curicana. Gestiona tus citas médicas en línea.
        </p>
        <div className="accesos">
          <a className="acceso" href="#/ingresar">Reservar hora <i class="fa-solid fa-book-bookmark"></i></a>
          <a className="acceso" href="#/ingresar">Especialidades <i class="fa-solid fa-user-doctor"></i></a>
          <a className="acceso" href="#/ingresar">Resultados <i class="fa-solid fa-square-poll-vertical"></i></a>
          <a className="acceso" href="#/ingresar">¿Cómo llegar? <i class="fa-solid fa-location-dot"></i></a>
        </div>
      </div>
    </section>
  );
}

/* ----- Código Div Noticias ----- */
function Noticias(){
  // Datos de ejemplo (puedes traerlos de una API luego)
  const items = [
    { id:1, titulo:"Campaña de vacunación influenza", fecha:"Sep 2025", img:"../img/vacunacion.png" },
    { id:2, titulo:"Nuevos cupos en especialidad de cardiología", fecha:"Ago 2025", img:"../img/cardiologia.png" },
    { id:3, titulo:"Mejoras en atención de urgencias", fecha:"Jul 2025", img:"../img/urgencia.jpg" },
  ];
  return (
    <section className="tarjeta" style={{marginTop:20}}>
      <h3 style={{marginTop:0}}>Noticias y avisos</h3>
      <div className="noticias-grid">
        {items.map(n=>(
          <article key={n.id} className="noticia">
            <img src={n.img} alt={n.titulo}/>
            <div className="cuerpo">
              <div className="meta">{n.fecha}</div>
              <h4>{n.titulo}</h4>
              <a className="enlace" href="#/ingresar">Leer más</a>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}

function Servicios(){
  const items = [
    {t:"Urgencia 24/7", d:"Atención inmediata para emergencias."},
    {t:"Imagenología", d:"Rayos X, ecografías y más."},
    {t:"Laboratorio", d:"Exámenes clínicos y resultados online."},
  ];
  return (
    <section className="tarjeta" style={{marginTop:20}}>
      <h3 style={{marginTop:0}}>Servicios destacados</h3>
      <div className="servicios">
        {items.map((s,i)=>(
          <div className="servicio" key={i}>
            <h4>{s.t}</h4>
            <p>{s.d}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

function EstadoApi(){
  const [ok, setOk] = useState(null);
  useEffect(()=>{ fetch(URL_API + "/salud").then(r=>setOk(r.ok)).catch(()=>setOk(false)); }, []);
  if (ok === null) return <span>Cargando…</span>;
  return <span className={ok ? "ok" : "err"}>{ok ? "Conectado" : "Sin conexión"}</span>;
}

function Inicio({ irA }){
  const token = localStorage.getItem("token");
  const [yo, setYo] = useState(null);
  const [error, setError] = useState(null);
  useEffect(()=>{
    (async ()=>{
      if (!token) return;
      // Esto solo trae el perfil, pero la redirección debe ocurrir después del login exitoso.
      try { setYo(await api("autenticacion/mi-perfil", { token })); } 
      catch(e){ setError(e.message); }
    })();
  }, [token]);

  return (
    <>
      <Barra
        autenticado={!!token}
        alSalir={()=>{localStorage.removeItem("token"); localStorage.removeItem("rol"); location.reload();}} // Limpiar token Y rol
        irA={irA}
      />
      <div className="contenedor">
        <div className="banner-acreditacion">
          <img src="../img/acreditacion.jpg" alt="Acreditación 2025 - Hospital de Curicó"/>
        </div>
        <Portada/>
        <Noticias/>
        <Servicios/>
        <footer className="pie-institucional">
          © {new Date().getFullYear()} Hospital de Curicó | Ministerio de Salud. <br/>
          Calle Juan Fernández 1890, Curicó, Maule. · Tel: (75) 228 3214
          <h3>
            - Desarrollado por: "Colo-Colo Team" -
          </h3>
        </footer>
      </div>
    </>
  );
}

function Ingresar({ irA }){
  const [correo, setCorreo] = useState("admin@hospital.cl"); // Correo de prueba ajustado
  const [contrasena, setContrasena] = useState("demo123"); // Contraseña de prueba
  const [cargando, setCargando] = useState(false);
  const [error, setError] = useState(null);
  
  const enviar = async (e)=>{
    e.preventDefault();
    setCargando(true); setError(null);
    try{
      // CRÍTICO: El Backend debe devolver el 'rol' del usuario
      const { token_acceso, rol } = await api("login.php", { metodo:"POST", cuerpo:{ correo, contrasena } }); 
      
      // 1. Guardar Token y Rol
      localStorage.setItem("token", token_acceso);
      localStorage.setItem("rol", rol);
      
      // 2. Redireccionar por Rol a la página HTML correspondiente
      if (rol === 'admin') {
        location.href = "admin.html";
      } else if (rol === 'doctor') {
        location.href = "doctor.html";
      } else if (rol === 'paciente') {
        location.href = "paciente.html";
      } else {
        // Rol desconocido, redirigir a inicio (o dar error)
        location.href = "inicio.html"; 
      }
      
    }catch(e){ setError(e.message); } finally{ setCargando(false); }
  };
  
  return (
    <>
      <Barra autenticado={false} alSalir={()=>{}} irA={irA}/>
      <div className="contenedor">
        <div className="tarjeta" style={{maxWidth:520, margin:"20px auto"}}>
          <h2>Ingresar</h2>
          <form onSubmit={enviar}>
            <label className="etiqueta">Correo</label>
            <input className="campo" type="email" value={correo} onChange={e=>setCorreo(e.target.value)} required/>
            <label className="etiqueta" style={{marginTop:10}}>Contraseña</label>
            <input className="campo" type="password" value={contrasena} onChange={e=>setContrasena(e.target.value)} required/>
            <div style={{display:"flex",gap:10, marginTop:14}}>
              <button className="boton primario" type="submit" disabled={cargando}>{cargando?"Ingresando…":"Ingresar"}</button>
              <button className="boton" type="button" onClick={()=>irA("/")}>Cancelar</button>
            </div>
            {error && <p className="err" style={{marginTop:10}}>Error: {error}</p>}
          </form>
        </div>
      </div>
    </>
  );
}

function App(){
  const [ruta, irA] = usarRuta();
  if (ruta === "/ingresar") return <Ingresar irA={irA}/>;
  return <Inicio irA={irA}/>;
}

const raiz = ReactDOM.createRoot(document.getElementById("aplicacion"));
raiz.render(<App/>);