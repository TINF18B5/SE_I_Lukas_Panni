<?php

/**
 * Class ProjectRepository
 * adding, selecting and updating Project-Objects, Shared-Projects and Team-Members from database
 */
class ProjectRepository extends Repository
{

    /**
     * @param $project Project
     * @return mixed
     */
    public function add($project)
    {
        $sql = "INSERT INTO project (ProjectName, ProjectManager, ProjectDescription) VALUES (:Name, :Manager, :Description)";
        $stmt = $this->dbConnection->prepare($sql);
        $res = $stmt->execute(array(":Name" => $project->getProjectName(), ":Manager" => $project->getProjectManager(), ":Description" => $project->getProjectDescription()));
        if ($res !== false) {
            return $this->dbConnection->lastInsertId();
        }
        return null;
    }

    /**
     * @param $project Project
     * @return bool
     */
    public function update($project)
    {
        $sql = "UPDATE project SET ProjectName=:Name, ProjectDescription=:Description WHERE ProjectId=:Project";
        $stmt = $this->dbConnection->prepare($sql);
        return $stmt->execute(array(":Name" => $project->getProjectName(), ":Description" => $project->getProjectDescription(), ":Project" => $project->getProjectId()));
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $stmt = $this->dbConnection->prepare("SELECT COUNT(ProjectId) FROM project");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * @param $id int
     * @return Project
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM project WHERE ProjectId=:Id";
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(array(":Id" => $id));
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Project');
        $res = $stmt->fetch();
        if ($res !== false) {
            return $res;
        }
        return null;
    }

    /**
     * @param $start int: startindex
     * @param $count int: total number of elements to retrieve
     * @return array
     */
    public function getMultiple($start, $count)
    {
        $sql = "SELECT * FROM project LIMIT :Start, :Rows";
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->bindParam(":Start", $start, PDO::PARAM_INT);
        $stmt->bindParam(":Rows", $count, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Project');
        $res = $stmt->fetchAll();
        if ($res !== false) {
            return $res;
        }
        return null;
    }

    /**
     * @param $user User
     * @return Project
     */
    public function getByProjectManager($user)
    {
        $sql = "SELECT * FROM project WHERE ProjectManager=:Manager";
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(array(":Manager" => $user->getUserId()));
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Project');
        $res = $stmt->fetchAll();
        if ($res !== false) {
            return $res;
        }
        return null;
    }

    /**
     * @param $token string
     * @return Project
     */
    public function getByToken($token)
    {
        $sql = "SELECT project.ProjectId, ProjectName, ProjectDescription, ProjectManager FROM project, sharedtoview WHERE project.ProjectId=sharedtoview.ProjectId AND sharedtoview.ExpirationDate > NOW() AND sharedtoview.AccessToken=:Token";
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(array(":Token" => $token));
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Project');
        $res = $stmt->fetch();
        if ($res !== false) {
            return $res;
        }
        return null;
    }

    /**
     * @param $project Project: project to share
     * @param $expirationDate string: date when share expires
     * @param $token string: token to access this shared-project
     * @return mixed
     */
    public function share($project, $expirationDate, $token)
    {
        $sql = "INSERT INTO sharedtoview (ExpirationDate, AccessToken, ProjectId) VALUES (:Expires, :Token, :Project)";
        $stmt = $this->dbConnection->prepare($sql);
        return $stmt->execute(array(":Expires" => $expirationDate, ":Token" => $token, ":Project" => $project->getProjectId()));
    }

    /**
     * @param $project Project
     * @return array
     */
    public function getMembers($project)
    {
        $sql = "SELECT user.UserId, user.Firstname, user.Lastname, user.EmailAddress FROM user, invited_to_work_on WHERE invited_to_work_on.UserId=user.UserId AND invited_to_work_on.ProjectId=:Project AND invited_to_work_on.Accepted IS TRUE";
        $stmt = $this->dbConnection->prepare($sql);
        $res = $stmt->execute(array(":Project" => $project->getProjectId()));
        if ($res !== false) {
            $stmt->setFetchMode(PDO::FETCH_CLASS, "User");
            return $stmt->fetchAll();
        }
        return null;
    }
}

?>