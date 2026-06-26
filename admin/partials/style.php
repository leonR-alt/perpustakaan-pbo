<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #F5F5F3;
    color: #1a1a1a;
    font-size: 14px;
  }
  .layout { display: flex; min-height: 100vh; }

  /* SIDEBAR */
  .sidebar {
    width: 220px;
    background: #1C1917;
    color: #fff;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
  }
  .sidebar-brand {
    padding: 20px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .sidebar-nav { flex: 1; padding: 12px 8px; }
  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 13px;
    border-radius: 8px;
    margin-bottom: 2px;
  }
  .sidebar-link:hover { background: rgba(255,255,255,0.06); color: #fff; }
  .sidebar-link.active { background: rgba(184,134,11,0.18); color: #FCD34D; font-weight: 500; }
  .sidebar-footer { padding: 14px 18px; border-top: 1px solid rgba(255,255,255,0.08); }
  .sidebar-footer a {
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .sidebar-footer a:hover { color: #fff; }

  /* MAIN */
  .main-content { flex: 1; min-width: 0; }
  .topbar {
    background: #fff;
    border-bottom: 1px solid #E5E5E0;
    padding: 0 24px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .topbar h1 { font-size: 16px; font-weight: 600; }
  .topbar-user { font-size: 13px; color: #666; }
  .content { padding: 24px; }

  /* METRICS */
  .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
  .metric { background: #fff; border: 1px solid #E5E5E0; border-radius: 12px; padding: 16px 18px; }
  .metric-label { font-size: 12px; color: #888; margin-bottom: 8px; }
  .metric-value { font-size: 24px; font-weight: 600; }
  .metric-sub { font-size: 11px; color: #aaa; margin-top: 4px; }

  /* CARD */
  .card { background: #fff; border: 1px solid #E5E5E0; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
  .card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid #E5E5E0; gap: 12px; flex-wrap: wrap;
  }
  .card-title { font-size: 14px; font-weight: 600; }

  /* BUTTONS */
  .btn {
    font-size: 13px; padding: 8px 16px; border-radius: 8px;
    border: 1px solid #D5D5D0; background: #fff; color: #1a1a1a;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn:hover { background: #F5F5F3; }
  .btn-primary { background: #534AB7; color: #fff; border-color: #534AB7; }
  .btn-primary:hover { background: #3C3489; }
  .btn-sm { font-size: 12px; padding: 5px 12px; }
  .btn-danger { background: #FCEBEB; color: #A32D2D; border-color: #F7C1C1; }
  .btn-danger:hover { background: #F7C1C1; }

  /* BADGES */
  .badge { display: inline-flex; align-items: center; font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 500; }
  .badge-success { background: #EAF3DE; color: #27500A; }
  .badge-warning { background: #FAEEDA; color: #633806; }
  .badge-danger { background: #FCEBEB; color: #791F1F; }

  /* TABLE */
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th {
    text-align: left; font-size: 11px; font-weight: 600; color: #888;
    padding: 10px 16px; border-bottom: 1px solid #E5E5E0; background: #FAFAF8;
    text-transform: uppercase; letter-spacing: 0.03em;
  }
  td { padding: 10px 16px; border-bottom: 1px solid #F0EFE8; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #FAFAF8; }
  .empty-row { text-align: center; color: #aaa; padding: 2rem !important; }

  /* FORM */
  .form-card { background: #fff; border: 1px solid #E5E5E0; border-radius: 12px; padding: 20px; max-width: 560px; }
  .form-group { margin-bottom: 14px; }
  .form-group label { font-size: 12px; font-weight: 500; color: #555; display: block; margin-bottom: 5px; }
  .form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 9px 12px; border: 1px solid #D5D5D0; border-radius: 8px;
    font-size: 13px; outline: none; font-family: inherit;
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: #534AB7; box-shadow: 0 0 0 3px rgba(83,74,183,0.12);
  }
  .form-actions { display: flex; gap: 8px; margin-top: 18px; }
  .multi-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
  .multi-row input, .multi-row select { flex: 1; }

  /* ALERT */
  .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
  .alert-success { background: #EAF3DE; color: #27500A; border: 1px solid #C0DD97; }
  .alert-danger { background: #FCEBEB; color: #791F1F; border: 1px solid #F7C1C1; }
  .alert-warning { background: #FAEEDA; color: #633806; border: 1px solid #FAC775; }

  .page-title { font-size: 18px; font-weight: 600; margin-bottom: 4px; }
  .page-sub { font-size: 13px; color: #888; margin-bottom: 20px; }

  .search-box { display: flex; align-items: center; gap: 8px; flex: 1; max-width: 320px; }
  .search-box input { flex: 1; padding: 8px 12px; border: 1px solid #D5D5D0; border-radius: 8px; font-size: 13px; outline: none; }
</style>